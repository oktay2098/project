<?php

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\Resources\DefinitionResource;
use App\Models\Car;
use App\Models\Definition;
use App\Models\ModelContent;
use App\Models\RealEstate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

//traits
use App\Traits\ApiResponserTrait;

class DefinitionController extends Controller
{
    use ApiResponserTrait;
    

    /**
     * Get active Definitions by filter.
     *
     * @return App\Http\Resources\DefinitionResource
     */
    public function index(Request $request)
    {
        $query = Definition::query();
        
        // check if the user is admin or not 
        if(!$this->user()?->isAdmin()){
            $query->where('status', 1);
        }
        
        // apply the filter
        $query->where('type', strtolower($request->type));

        if($request->group){
            $query->where('payload->group', strtolower($request->group));
        }

        // make the default request limit to 200
        if(!$request->limit){
            $request->limit = 200;
        }

        $definitions = $query->paginate($request->limit);
        return DefinitionResource::collection($definitions);
    }


    /**
     * Store new Definition.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->checkJSON();

        $data = (array) $request->all();

        $validator = Validator::make($data, [
            'translations' => ['required', 'array'],
            'translations.*.title' => ['required', 'max:250'],
            'translations.*.language' => ['required', 'max:6'],
            'type' => ['required', 'max:60', Rule::in(Definition::types)],
            'status' => ['required', Rule::in([0, 1])],
            'group' => ['nullable', 'max:60'],
            'additional' => ['nullable', 'array'],
            'additional.*' => ['required', 'string',],
        ])->validate();

        $definition = new Definition();
        $definition->type = strtolower($data['type']);
        $definition->status = $data['status'];

        foreach($data["translations"] as $translation) {
            $definition->languages = array_merge([$translation['language']], $definition->languages ?? []);
            $definition->setTranslation("title", $translation["language"], $translation["title"]);
        }
        if ($request->group) {
            $definition->payload = [
                'group' => strtolower($data['group']),
            ];
        }
        if ($request->additional) {
            foreach ($request->additional as $key => $value) {
                $definition->payload = array_merge([$key => $value], $definition->payload ?? []);
            }
        }

        $definition->save();

        return  [
            'status'  => true,
            'definition' => $definition
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $definition_id
     * @return \Illuminate\Http\Response
     */
    public function show($definition_id)
    {
        $definition = Definition::findOrFail($definition_id);

        return  [
            'status'  => true,
            'definition' => $definition
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

        $data = (array) $request->all();
        

        $validator = Validator::make($data, [
            'translations' => ['required', 'array'],
            'translations.*.title' => ['required', 'max:250'],
            'translations.*.language' => ['required', 'max:6'],
            'type' => ['required', 'max:60', Rule::in(Definition::types)],
            'status' => ['required', Rule::in([0, 1])],
            'group' => ['nullable', 'max:60'],
            'additional' => ['nullable', 'array'],
            'additional.*' => ['required', 'string',],
        ])->validate();

        $definition = Definition::findOrFail($data['id']);
        
        foreach($data["translations"] as $translation) {
            if (!in_array($translation['language'], $definition->languages)) {
                $definition->languages = array_merge([$translation['language']], $definition->languages ?? []);
            }
            $definition->setTranslation("title", $translation["language"], $translation["title"]);
        }

        $definition->type = strtolower($data['type']);
        $definition->status = $data['status'];

        $definition->payload = [];

        if ($request->additional) {
            foreach ($request->additional as $key => $value) {
                $definition->payload = array_merge([$key => $value], $definition->payload ?? []);
            }
        }

        if ($request->group || isset($request->additional['group'])) {
            $definition->payload = [
                'group' => strtolower($data['group']),
            ];
        }

        $definition->save();

        return  [
            'status'  => true,
            'definition' => $definition
        ];
    }

    public function filter(Request $request)
    {
        
        //body_style only corporate

        $body_style_id = $request->input('body_style_id');
        $brand_id = $request->input('brand_id');
        $fuel_type_id = $request->input('fuel_type_id');
        $interior_color_id = $request->input('interior_color_id');
        $exterior_color_id = $request->input('exterior_color_id');
        $model = $request->input('model');
        $gear_type = $request->input('gear_type');
        $min_price = $request->input('min_price');
        $max_price = $request->input('max_price');
        $min_year = $request->input('min_year');
        $max_year = $request->input('max_year');

        $min_km = $request->input('min_km');
        $max_km = $request->input('max_km');
        $cars = new Car;
        $cars = $cars->with('car_brand:id,title');

        // filter by address
        if ($request->address) {
            $q = Address::select('id')
                ->where(function ($wQuery) use ($request) {
                    $wQuery->where('country_code', 'Like', '%' . $request->address . '%')
                        ->orWhere('country', 'Like', '%' . $request->address . '%')
                        ->orWhere('administrative_1', 'Like', '%' . $request->address . '%')
                        ->orWhere('administrative_2', 'Like', '%' . $request->address . '%')
                        ->orWhere('administrative_3', 'Like', '%' . $request->address . '%')
                        ->orWhere('administrative_4', 'Like', '%' . $request->address . '%')
                        ->orWhere('administrative_5', 'Like', '%' . $request->address . '%')
                        ->orWhere('postal_code', 'Like', '%' . $request->address . '%')
                        ->orWhere('street_number', 'Like', '%' . $request->address . '%')
                        ->orWhere('formatted_address', 'Like', '%' . $request->address . '%');
                });


            if ($request->country) {
                $q->where('country',  $request->country);
            }

            $addresses_ids = $q->get()?->toArray();

            $cars->join('addresses', function ($join) {
                $join->on('addresses.model_id', '=', 'cars.id')
                    ->where('addresses.model_type', '=', Car::class);
            })->whereIn('addresses.id', $addresses_ids);

        }


        if($body_style_id != NULL) {
            $cars = $cars->where('body_style_id', $body_style_id);
        }

        if($brand_id != NULL) {
            $cars = $cars->where('brand_id', $brand_id);
        }
        if($fuel_type_id != NULL) {
            $cars = $cars->where('fuel_type_id', $fuel_type_id);
        }
        if($interior_color_id != NULL) {
            $cars = $cars->where('interior_color_id', $interior_color_id);
        }
        if($exterior_color_id != NULL) {
            $cars = $cars->where('exterior_color_id', $exterior_color_id);
        }
        if($model != NULL) {
            $cars = $cars->where('model', $model);
        }
        if($gear_type != NULL) {
            $cars = $cars->where('transmission_id', $gear_type);
        }
        if($min_price != NULL and $max_price != NULL) {
            $cars = $cars->whereBetween('price', [$min_price, $max_price]);
        }
        if($min_price == NULL and $max_price != NULL) {
            $cars = $cars->where('price', '<=', $max_price);
        }
        if($min_price != NULL and $max_price == NULL) {
            $cars = $cars->where('price', '>=', $min_price);
        }

        if($min_year != NULL and $max_year != NULL) {
            $cars = $cars->whereBetween('year', [$min_year, $max_year]);
        }
        if($min_year == NULL and $max_year != NULL) {
            $cars = $cars->where('year',  '<=',$max_year);
        }
        if($min_year != NULL and $max_year == NULL) {
            $cars = $cars->where('year',  '>=', $min_year);
        }

        if($min_km != NULL and $max_km != NULL) {
            $cars = $cars->whereBetween('kilometre', [$min_km, $max_km]);
        }
        if($min_km == NULL and $max_km != NULL) {
            $cars = $cars->where('kilometre',  '<=',$max_km);
        }
        if($min_km != NULL and $max_km == NULL) {
            $cars = $cars->where('kilometre',  '>=', $min_km);
        }
        $cars = $cars->get();

        return $this->successResponse($cars, 'filter_car', '', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $definition_id
     * @return \Illuminate\Http\Response
     */
    public function destroy($definition_id)
    {
        $definition = Definition::findOrFail($definition_id);


        if ($definition->delete()) {
            return  [
                'status'  => true,
            ];
        } else {
            throw new UnprocessableContentException();
        }
    }


}
