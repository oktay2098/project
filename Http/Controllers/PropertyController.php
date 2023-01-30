<?php

namespace App\Http\Controllers;

use App\Http\Resources\PropertiesResource;
use App\Models\Address;
use App\Models\Definition;
use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use \App\Models\Properties as PropertiesModel;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{

    public function propertiesValidationRules($rules = [])
    {
        return array_merge([
            'translatable' => 'required|array',
            'translatable.*.title' => 'required|string',
            'translatable.*.description' => 'required|string',
            'translatable.*.meta_desc' => 'required|string',
            'translatable.*.language' => ['required', 'string', Rule::in(config('app.locales'))],
            'living_room_number' => 'required',
            'bedrooms_number' => 'required|integer',
            'bathrooms_number' => 'required|integer',
            'floor_number' => 'required|integer',
            'price' => 'required',
            'currency_id' => 'required|integer',
            'square' => 'required',
            'category_id' => ['required', 'integer', 'exists:definitions,id,type,' . Definition::types['properties_category']],
            'status' => 'required|integer',
            'type_id' => 'required|integer',
            'facilities' => 'nullable|array',
            'facilities.*.id' => ['required', 'integer', 'exists:definitions,id,type,' . Definition::types['facility']],
            'facilities.*.distance' => 'required|integer',
            'features' => 'nullable|array',
            'features.*' => ['required', 'integer', 'exists:definitions,id,type,' . Definition::types['property feature']],
            'address' => ['required', 'array'],
            'address.inserted' => ['required_unless:address,null', 'string'],
            'address.geodata' => ['required_unless:address,null', 'array'],
        ], $rules);
    }


    public function index(Request $request)
    {
        $query = PropertiesModel::query();


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

            $query->select('properties.*')->join('addresses', function ($join) {
                $join->on('addresses.model_id', '=', 'properties.id')
                    ->where('addresses.model_type', '=', PropertiesModel::class);
            })->whereIn('addresses.id', $addresses_ids);

        }

        if ($request->category and $request->category != NULL) {
            $category = explode(",", $request->category);
            $query->whereIn('category_id', $category);
        }
        if ($request->type_id  and $request->type_id != NULL) {
            $type_id = explode(",", $request->type_id);
            $query->whereIn('type_id', $type_id);
        }

        if ($request->features and  $request->features != NULL) {
            $features = explode(",", $request->features);
            $query->whereHas('features', function ($q) use ($features) {
                $q->whereIn('feature_id', $features);
            });
        }


        // TODO: make the price filter compatible with the deferent currencies
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('bedrooms_number')) {
            $query->where('bedrooms_number', $request->bedrooms_number);
        }

        if ($request->has('min_size') and $request->min_size != NULL) {
            $query->where('square', '>=', $request->min_size);
        }

        if ($request->has('max_size') and $request->max_size != NULL) {
            $query->where('square', '<=', $request->max_size);
        }

        // check if the user is admin or not 
        if (!$this->user()?->isAdmin()) {
            $query->where('status', "1");
        } elseif ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('order_by') && $request->has('order_direction')) {
            !in_array($request->order_direction, ['desc', 'asc']) ? $request->order_direction = 'desc' : '';
            $orderBy = null;

            if ($request->order_by == 'price') {
                $orderBy = 'price';
            } elseif ($request->order_by == 'last_updated') {
                $orderBy = 'updated_at';
            } elseif ($request->order_by == 'size') {
                $orderBy = 'square';
            }

            if ($orderBy) {
                $query->orderBy($orderBy, $request->order_direction);
            }
        }


        $properties = $query->paginate($request->limit);
        return PropertiesResource::collection($properties);
    }

    public function getByUser(Request $request)
    {
        $user = $this->user();
        $query = PropertiesModel::query();

        if ($request->has('order_by') && $request->has('order_direction')) {
            !in_array($request->order_direction, ['desc', 'asc']) ? $request->order_direction = 'desc' : '';
            $orderBy = null;

            if ($request->order_by == 'price') {
                $orderBy = 'price';
            } elseif ($request->order_by == 'last_updated') {
                $orderBy = 'updated_at';
            } elseif ($request->order_by == 'size') {
                $orderBy = 'square';
            }

            if ($orderBy) {
                $query->orderBy($orderBy, $request->order_direction);
            }
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query->where('author_id', $user->id);

        $properties = $query->paginate($request->limit);

        return PropertiesResource::collection($properties);
    }

    public function slider(Request $request)
    {
        $query = PropertiesModel::inRandomOrder();




        // check if the user is admin or not 



        $properties = $query->take(10)->get();
        return PropertiesResource::collection($properties);
    }
    public function store(Request  $request)
    {

        if (!$this->hasSubscription()) {
            return response()->json([
                'message' => __('You have no subscription'),
            ], 403);
        }

        if (!$this->canCreateNew()) {
            return response()->json([
                'message' => __('you can\'t create more properties'),
            ], 403);
        }

        $data = $request->all();
        $validated = Validator::make($data, $this->propertiesValidationRules())->validate();


        $property = new PropertiesModel();

        foreach ($request->translatable as $translation) {
            $property->setTranslation("title", $translation["language"], $translation["title"]);
            $property->setTranslation("description", $translation["language"], $translation["description"]);
            $property->setTranslation("meta_desc", $translation["language"], $translation["meta_desc"]);
            $property->languages = !in_array($translation['language'],  $property->languages ?? []) ? array_merge([$translation['language']], $property->languages ?? []) :  $property->languages;
        }


        $property->bedrooms_number = $request->bedrooms_number;
        $property->living_room_number = $request->living_room_number;
        $property->bathrooms_number = $request->bathrooms_number;
        $property->price = $request->price;
        $property->floor_number = $request->floor_number;

        $property->square = $request->square;
        $property->author_id = $this->currentUserId();
        $property->category_id = $request->category_id;
        $property->status = $request->status;
        // $property->address = $request->address;
        $property->type_id = $request->type_id;
        $property->currency_id = $request->currency_id;

        $property->save();

        $this->user()->decreasePackageBalance('regular', 'properties');

        $addressController = new AddressController();
        $addressController->createOrUpdate($data['address'], $property);


        if (isset($data['facilities'])) {
            foreach ($data['facilities'] as $facility) {
                $property->facilities()->attach($facility['id'], ['distance' => $facility['distance']]);
            }
        }


        if (isset($data['features'])) {
            $property->features()->attach($data['features']);
        }

        $property->addAllMediaFromTokens();

        return $this->success([
            'property' => $property
        ]);
    }

    public function update(Request $request)
    {

        if (!$this->hasSubscription()) {
            return response()->json([
                'message' => __('You have no subscription'),
            ], 403);
        }

        $data = $request->all();
        $validated = Validator::make($data, $this->propertiesValidationRules([
            'id' => ['required', 'integer', 'exists:properties,id,deleted_at,NULL'],
            'address' => ['nullable', 'array'],
        ]))->validate();

        $property = PropertiesModel::findOrFail($data['id']);

        if ($property->author_id != $this->currentUserId() && !$this->user()?->isAdmin()) {
            return response()->json([
                'message' => __('You are not authorized to update this property'),
            ], 403);
        }

        foreach ($request->translatable as $translation) {
            $property->setTranslation("title", $translation["language"], $translation["title"]);
            $property->setTranslation("description", $translation["language"], $translation["description"]);
            $property->setTranslation("meta_desc", $translation["language"], $translation["meta_desc"]);
            $property->languages = !in_array($translation['language'],  $property->languages ?? []) ? array_merge([$translation['language']], $property->languages ?? []) :  $property->languages;
        }

        $property->bedrooms_number = $request->bedrooms_number;
        $property->living_room_number = $request->living_room_number;
        $property->bathrooms_number = $request->bathrooms_number;
        $property->price = $request->price;
        $property->floor_number = $request->floor_number;

        $property->square = $request->square;
        $property->author_id = $this->currentUserId();
        $property->category_id = $request->category_id;
        $property->status = $request->status;
        $property->type_id = $request->type_id;
        $property->currency_id = $request->currency_id;

        $property->save();

        if (isset($data['address'])) {
            $addressController = new AddressController();
            $addressController->createOrUpdate($data['address'], $property, $property->address?->id);
        }


        if (isset($data['facilities'])) {
            $property->facilities()->detach();
            foreach ($data['facilities'] as $facility) {
                $property->facilities()->attach($facility['id'], ['distance' => $facility['distance']]);
            }
        }

        if (isset($data['features'])) {
            $property->features()->detach();
            $property->features()->attach($data['features']);
        }

        $property->addAllMediaFromTokens();

        return $this->success([
            'property' => $property
        ]);
    }

    public function destroy($id)
    {
        $property = PropertiesModel::find($id);
        if ($property->delete()) {
            return $this->success([]);
        } else {
            throw new Exception(__('Unknown An error occurred'));
        }
    }

    public function show($id)
    {

        $property = PropertiesModel::findOrFail($id);
        $property->load('author');

        return $this->success([
            'property' => $property
        ]);
    }

    public function hasSubscription()
    {
        if ($this->user()?->subscription_status['properties']['status'] == 1) {
            return true;
        }
        return false;
    }

    public function canCreateNew()
    {
        if ($this->hasSubscription()) {
            if ($this->user()?->subscription_status['properties']['balance'] > 0) {
                return true;
            }
        }
        return false;
    }

    public function markAsBoostUp(Request $request)
    {
        $data = $request->all();
        Validator::make($data, [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'exists:properties,id,deleted_at,NULL'],
        ])->validate();

        $boostedUpCount = PropertiesModel::where('boosted_up', 1)
            ->where('author_id', $this->currentUserId())->whereNotIn('id', $data['ids'])->count();

        if ($this->user()?->subscription_status['properties']['boost_up'] - $boostedUpCount >= count($data['ids'])) {
            $marked = 0;
            foreach ($data['ids'] as $propertyId) {
                $property = PropertiesModel::findOrFail($propertyId);
                if ($property->boosted_up == 0) {
                    $property->boosted_up = 1;
                    $property->save();
                    $marked++;
                }
            }

            return response()->json([
                'message' => __(':count ads marked as boost up', ['count' => $marked]),
            ], 200);
        } else {
            return response()->json([
                'message' => __("You don't have enough BoostUp credits"),
            ], 403);
        }
    }

    public function removeBoostUp(Request $request)
    {
        $data = $request->all();
        Validator::make($data, [
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'exists:properties,id,deleted_at,NULL'],
        ])->validate();

        foreach ($data['ids'] as $propertyId) {
            $property = PropertiesModel::findOrFail($propertyId);
            $property->boosted_up = 0;
            $property->save();
        }

        return response([
            'success' => true,
        ]);
    }
}
