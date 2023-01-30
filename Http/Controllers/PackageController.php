<?php

namespace App\Http\Controllers;

use App\Exceptions\UnprocessableContentException;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\PackagePrice;
use App\Services\IyzicoPaymentService;
use Carbon\Carbon;
use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;;

class PackageController extends Controller
{

    public function validationRules($rules = [])
    {
        return array_merge([
            'title' => ['required', 'string', 'max:250'],
            'country' => ['required', 'string', 'in:turkey,global'],
            'status' => ['required', Rule::in([0, 1])],
            'period' => ['required', 'integer', 'min:1'],
            'type' => ['required', Rule::in(['boost_up', 'regular'])],
            'user_type' => ['required', Rule::in(['agent', 'agency'])],
            'ads_type' => ['required', Rule::in(['cars', 'properties'])],
            'language' => ['required'],
            'currency_id' => ['required', 'exists:currencies,id,deleted_at,NULL'],
            'currency_code' => ['required', 'exists:currencies,code'],
            'items' => ['required', 'array'],
            'items.*.id' => ['nullable', 'exists:package_items,id,deleted_at,NULL'],
            'items.*.ads_number' => ['required', 'integer', 'min:1'],
            'items.*.price_per_month' => ['required', 'numeric', 'min:0'],
            'items.*.status' => ['required', Rule::in([0, 1])],

        ], $rules);
    }


    /**
     * Get packages.
     *
     * @return App\Http\Resources\PackageResource
     */
    public function index(Request $request)
    {
        $query = Package::query();

        // check if the user is not admin
        if (!$this->user()?->isAdmin()) {
            $query->where('status', 1);


            if ($this->user()?->isLocal()) {
                $query->where('country', 'turkey');
            } else {
                $query->where('country', 'global');
            }
        }

        
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->has('ads_type')) {
            $query->where('ads_type', $request->get('ads_type'));
        }

        if ($request->has('user_type')) {
            $query->where('user_type', $request->get('user_type'));
        }

        $query->orderBy('period', 'asc');
        $packages = $query->paginate($request->limit ?? 200);


        return PackageResource::collection($packages);
    }


    /**
     * Store new Package.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->checkJSON();

        $data = (array) $request->input('data');

        $validator = Validator::make($data, $this->validationRules())->validate();

        $paymentService = new IyzicoPaymentService();

        $package = new Package();
        $package->setTranslation('title', $data['language'], $data['title']);
        $package->country = $data['country'];
        $package->type = $data['type'];
        $package->user_type = $data['user_type'];
        $package->ads_type = $data['ads_type'];
        $package->status = $data['status'];
        $package->period = $data['period'];
        $package->languages = [$data['language']];
        $package->save();

        $subscriptionProduct = $paymentService->createSubscriptionProduct($package->title . ' ID: ' . $package->id, $package->type);

        if ($subscriptionProduct->getReferenceCode() == null) {
            $package->delete();
            return response([
                'message' => __("Payment error! couldn't create the product code!")
            ]);
        }
        $package->subscription_product_code = $subscriptionProduct->getReferenceCode();
        $package->save();

        if (isset($data['items'])) {
            $items = [];
            foreach ($data['items'] as $item) {

                $subscriptionPlan = $paymentService->createPricingPlan(
                    productCode: $subscriptionProduct->getReferenceCode(),
                    name: $item['ads_number'] . ' ' . $item['price_per_month'] . ' ' . $package->currency_code,
                    price: $item['price_per_month'] * $package->period,
                    paymentInterval: "MONTHLY",
                    paymentIntervalCount: $data['period'],
                    currency: $data['currency_code']
                );

                // if the plan not created in the payment gateway it wont be created in local
                if ($subscriptionPlan->getReferenceCode() ==  null) {
                    continue;
                }

                $items[] = [
                    'package_id' => $package->id,
                    'subscription_plan_code' => $subscriptionPlan->getReferenceCode(),
                    'ads_number' => $item['ads_number'],
                    'price' => $item['price_per_month'],
                    'currency_id' => $data['currency_id'],
                    'status' => $item['status'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            PackageItem::insert($items);
        }

        $package->load('items');


        return  response([
            'status'  => true,
            'package' => $package
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $package_id
     * @return \Illuminate\Http\Response
     */
    public function show($package_id)
    {
        $package = Package::findOrFail($package_id);

        return  [
            'status'  => true,
            'package' => $package
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->checkJSON();

        $paymentService = new IyzicoPaymentService();

        $data = (array) $request->input('data');

        $validator = Validator::make($data, $this->validationRules([
            'id' => ['required', 'exists:packages,id,deleted_at,NULL'],
        ]))->validate();

        $package = Package::find($data['id']);
        $package->setTranslation('title', $data['language'], $data['title']);
        $package->country = $data['country'];
        $package->type = $data['type'];
        $package->user_type = $data['user_type'];
        $package->ads_type = $data['ads_type'];
        $package->status = $data['status'];
        $package->period = $data['period'];
        if (!in_array($data['language'], $package->languages)) {
            $package->languages = array_merge([$data['language']], $package->languages);
        }

        if ($package->subscription_product_code) {
            $paymentService->updateSubscriptionProduct($package->subscription_product_code, $package->title . ' ID: ' . $package->id, $package->type);
        }

        $package->save();


        if (isset($data['items'])) {
            $items = [];
            foreach ($data['items'] as $item) {
                $items[] = [
                    'package_id' => $package->id,
                    'id' => $item['id'] ?? null,
                    'ads_number' => $item['ads_number'],
                    'price' => $item['price_per_month'],
                    'currency_id' => $data['currency_id'],
                    'status' => $item['status'],
                ];
            }
            PackageItem::upsert($items, ['id'], ['ads_number', 'price', 'currency_id', 'status']);
        }
        $package->load('items');

        foreach ($package->items as $item) {
            if ($item->subscription_plan_code) {
                $paymentService->updatePricingPlan($item->subscription_plan_code, $item->ads_number . ' ' . $item->total, $package->type);
            } else {
                $subscriptionPlan = $paymentService->createPricingPlan(
                    productCode: $package->subscription_product_code,
                    name: $item->ads_number . ' ' . $item->total . ' ' . $item->currency_code,
                    price: $item->total,
                    paymentInterval: "MONTHLY",
                    paymentIntervalCount: $package->period,
                    currency: $item->currency_code
                );

                $item->subscription_plan_code = $subscriptionPlan->getReferenceCode();
                $item->save();
            }
        }

        return  [
            'status'  => true,
            'package' => $package
        ];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $package_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($package_id)
    {
        $package = null; //Package::findOrFail($package_id);

        if ($package?->delete()) {
            return  [
                'status'  => true,
            ];
        } else {
            throw new UnprocessableContentException();
        }
    }
}
