<?php

namespace App\Http\Controllers;

use App\Exceptions\UnprocessableContentException;
use App\Http\Resources\CurrencyResource;
use App\Models\Currency;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{   
  
    public function validationRules($rules = [])
    {
        return array_merge([
            'title' => ['required', 'string', 'max:250'],
            'code' => ['required', 'string', 'min:3', 'max:3', Rule::in(config('app.currencies')), 'unique:currencies,code'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'status' => ['required', Rule::in([0, 1])],
            'is_prefix_symbol' => ['required', Rule::in(['0', '1'])],
            'is_default' => ['required', Rule::in(['0', '1'])],
            'language' => ['required', 'max:6'],
        ], $rules);
    }
    /**
     * Get all active Currencies with current language.
     *
     * @return App\Http\Resources\CurrencyResource
     */
    public function index(Request $request)
    {

        $query = Currency::query();
        if (!$this->user()->isAdmin()) {
            $query->where('status', 1);
        }
        $currencies = $query->paginate($request->limit);

        return CurrencyResource::collection($currencies);
    }

    /**
     * Store new Currency.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->checkJSON();

        $data = (array) $request->input('data');

        $validator = Validator::make($data, $this->validationRules())->validate();


        $currency = new Currency();
        $currency->setTranslation('title', $data['language'], $data['title']);
        $currency->symbol = $data['symbol'] ?? null;
        $currency->code = strtoupper($data['code']);
        $currency->status = $data['status'];
        $currency->is_prefix_symbol = $data['is_prefix_symbol'];
        $currency->languages = [$data['language']];
        $currency->is_default = $data['is_default'];
        $currency->save();


        $responseData =  [
            'status'  => true,
            'currency' => $currency
        ];

        return $responseData;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $currency_id
     * @return \Illuminate\Http\Response
     */
    public function show($currency_id)
    {
        $currency = Currency::findOrFail($currency_id);

        $responseData =  [
            'status'  => true,
            'currency' => $currency
        ];

        return $responseData;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $currency_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->checkJSON();


        $data = (array) $request->input('data');

        $validator = Validator::make($data, $this->validationRules(
            [
                'id' => ['required', 'integer', 'exists:currencies,id'],
                'code' => ['required', 'string', 'min:3', 'max:3', Rule::in(config('app.currencies')), 'unique:currencies,code,'.($data['id'] ?? null)  ],
            ]
        ))->validate();

        $currency = Currency::find($data['id']);
        $currency->setTranslation('title', $data['language'], $data['title']);
        $currency->code = strtoupper($data['code']);
        $currency->symbol = $data['symbol'] ?? null;
        $currency->status = $data['status'];
        $currency->is_prefix_symbol = $data['is_prefix_symbol'];
        $currency->is_default = $data['is_default'];
        if (!in_array($data['language'], $currency->languages)) {
            $currency->languages = array_merge([$data['language']], $currency->languages);
        }
        $currency->save();

        $responseData =  [
            'status'  => true,
            'currency' => $currency
        ];
        return $responseData;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $currency_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($currency_id)
    {

        // $currency = Currency::findOrFail($currency_id);

        // if ($currency->delete()) {
        //     $responseData =  [
        //         'status'  => true,
        //     ];
        //     return $responseData;
        // } else {
        //     throw new UnprocessableContentException();
        // }
    }

    public function exchange(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'from' => ['required', 'string', 'min:3', 'max:3'],
            'to' => ['required', 'string', 'min:3', 'max:3'],
            'amount' => ['required', 'numeric'],
        ])->validate();

        $exchangeService = new ExchangeRateService();
        $exchange = $exchangeService->convert( $data['amount'], $data['from'], $data['to']);

        return response()->json([
            'status' => true,
            'exchange' => $exchange
        ]);
    }
}

