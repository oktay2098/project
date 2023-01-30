<?php
namespace App\Http\Controllers;

use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Address;
use App\Http\Resources\AddressResource;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request      $request
     * @return Array
     */
    public function index(Request $request)
    {
        try {
            $query = $request->query();
            
            Gate::authorize('viewAddresses', [Address::class, $query]);
            
            $filter = [
                'AND' => [
                    ['deleted_at', '=', NULL]
                ],
                'OR'  => []
            ];
            $sort   = isset($query['sort']) && !empty(trim($query['sort'])) ? trim($query['sort'])            : 'country';
            $order  = isset($query['dir'])  && !empty(trim($query['dir']))  ? strtoupper(trim($query['dir'])) : 'ASC';
            $limit  = isset($query['limit'])                                ? (int) $query['limit']           : 0;
            $page   = isset($query['page'])                                 ? (int) $query['page']            : 1;
            
            if ( isset($query['s']) && !empty(trim($query['s'])) ) $filter['OR'][] = [
                ['formatted_address', 'like', '%'. trim($query['s']) . '%'],
                ['country',           'like', '%'. trim($query['s']) . '%'],
                ['administrative_1',  'like', '%'. trim($query['s']) . '%'],
                ['administrative_2',  'like', '%'. trim($query['s']) . '%'],
                ['administrative_3',  'like', '%'. trim($query['s']) . '%'],
                ['administrative_4',  'like', '%'. trim($query['s']) . '%'],
                ['locality',          'like', '%'. trim($query['s']) . '%'],
                ['route',             'like', '%'. trim($query['s']) . '%']
            ];
            
            if ( isset($query['model_type']) && !empty(trim($query['model_type'])) ) {
                $filter['AND'][] = ['model_type', '=', trim($query['model_type'])];
            }
            
            if ( isset($query['model_id']) && !empty(trim($query['model_id'])) ) {
                $filter['AND'][] = ['model_id', '=', trim($query['model_id'])];
            }
            
            if ( isset($query['country_code']) && !empty(trim($query['country_code'])) ) {
                $filter['AND'][] = ['country_code', '=', trim($query['country_code'])];
            }
            
            if ( isset($query['language']) && !empty(trim($query['language'])) ) {
                $filter['AND'][] = ['language', '=', trim($query['language'])];
            }
            
            if ( isset($query['country']) && !empty(trim($query['country'])) ) {
                $filter['AND'][] = ['country', '=', trim($query['country'])];
            }
            
            if ( isset($query['administrative_1']) && !empty(trim($query['administrative_1'])) ) {
                $filter['AND'][] = ['administrative_1', '=', trim($query['administrative_1'])];
            }
            
            if ( isset($query['administrative_2']) && !empty(trim($query['administrative_2'])) ) {
                $filter['AND'][] = ['administrative_2', '=', trim($query['administrative_2'])];
            }
            
            if ( isset($query['administrative_3']) && !empty(trim($query['administrative_3'])) ) {
                $filter['AND'][] = ['administrative_3', '=', trim($query['administrative_3'])];
            }
            
            if ( isset($query['administrative_4']) && !empty(trim($query['administrative_4'])) ) {
                $filter['AND'][] = ['administrative_4', '=', trim($query['administrative_4'])];
            }
            
            if ( isset($query['locality']) && !empty(trim($query['locality'])) ) {
                $filter['AND'][] = ['locality', '=', trim($query['locality'])];
            }
            
            if ( isset($query['route']) && !empty(trim($query['route'])) ) {
                $filter['AND'][] = ['route', '=', trim($query['route'])];
            }
            
            $builder = Address::where($filter['AND']);
            
            if ( count($filter['OR']) ) {
                foreach ( $filter['OR'] as $or ) {
                    if ( count($or) > 1 ) {
                        $builder->where(function($q) use ($or) {
                            $q->where([$or[0]]);
                            
                            for ( $i = 1; $i < count($or); $i++ ) {
                                $q->orWhere([$or[$i]]);
                            }
                        });
                    }
                }
            }
            
            $addresses = $builder
                         ->orderBy($sort, $order)
                         ->paginate($limit);
            $addresses = AddressResource::collection($addresses)->response()->getData(true);
            
            return $this->success([
                'resource' => $addresses
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int            $id
     * @return Array
     */
    public function show(int $id)
    {
        try {
            $address = Address::find($id);
            
            if ( !is_object($address) ) {
                throw new Exception(__('Could not find address with this id!'));
            }
            
            if ( !class_exists($address->model_type) ) {
                throw new Exception(__('err_address_model_type_class_not_found'));
            }
            
            $model = $address->model_type::find( (int) $address->model_id );
            
            if ( !is_object($model) ) {
                throw new Exception(__('err_address_object_not_found'));
            }
            
            Gate::authorize('view', [$address, $model]);
            
            $data               = $address->toArray();
            $data['created_at'] = $address->created_at ? $address->created_at->format('d/m/Y H:i:s') : null;
            $data['updated_at'] = $address->updated_at ? $address->updated_at->format('d/m/Y H:i:s') : null;
            $data['owner']      = $address->owner;
            
            unset($data['deleted_at']);
            
            return $this->success([
                'address' => $data
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  Request      $request
     * @param  int          $id
     * @return Array
     */
    public function update(int $id, Request $request)
    {
        try {
            if ( !$request->isJson() || !$request->accepts(['application/json']) ) {
                throw new Exception(__('err_invalid_request'));
            }
            
            $address = Address::find($id);
            
            if ( !is_object($address) ) {
                throw new Exception(__('Could not find address with this id!'));
            }
            
            if ( !class_exists($address->model_type) ) {
                throw new Exception(__('err_address_model_type_class_not_found'));
            }
            
            $model = $address->model_type::find( (int) $address->model_id );
            
            if ( !is_object($model) ) {
                throw new Exception(__('err_address_object_not_found'));
            }
            
            Gate::authorize('update', [$address, $model]);
            
            $data = (array) $request->input('data');
            
            unset($data['model_type']);
            unset($data['model_id']);
            
            if ( isset($data['latitude']) ) {
                $data['latitude'] = number_format($data['latitude'], 6, '.');
            }
            
            if ( isset($data['longitude']) ) {
                $data['longitude'] = number_format($data['longitude'], 6, '.');
            }
            
            $address->fill( $data );
            $this->validateData(array_filter($address->toArray(), function($v, $k) {
                return !empty($v);
                
            }, ARRAY_FILTER_USE_BOTH));
            
            if ( !$address->update() ) {
                throw new Exception(__('Could not update the address!'));
            }
            
            return $this->success([
                'address' => $address
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  Request      $request
     * @return Array
     */
    public function store(Request $request)
    {
        try {
            if ( !$request->isJson() || !$request->accepts(['application/json']) ) {
                throw new Exception(__('err_invalid_request'));
            }
            
            $data = (array) $request->input('data');
            
            if ( isset($data['latitude']) ) {
                $data['latitude'] = number_format($data['latitude'], 6, '.');
            }
            
            if ( isset($data['longitude']) ) {
                $data['longitude'] = number_format($data['longitude'], 6, '.');
            }
            
            $data['language'] = isset($data['language']) && !empty(trim($data['language'])) ? trim($data['language']) : App::currentLocale();
            
            $this->validateData($data);
            
            $model_class = 'App\\Models\\'.ucfirst(strtolower($data['model_type']));
            
            if ( !class_exists($model_class) ) {
                throw new Exception(__('err_address_model_type_class_not_found'));
            }
            
            $model = $model_class::find( (int) $data['model_id'] );
            
            if ( !is_object($model) ) {
                throw new Exception(__('err_address_object_not_found'));
            }
            
            Gate::authorize('create', [Address::class, $model]);
            
            $data['model_type'] = $model_class;
            $address            = Address::create($data);
            
            if ( !$address ) {
                throw new Exception(__('Could not create the address!'));
            }
            
            return $this->success([
                'address' => $address
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int       $id
     * @return Array
     */
    public function destroy($id)
    {
        try {
            $address = Address::find($id);
            
            if ( !is_object($address) ) {
                throw new Exception(__('Could not find address with this id!'));
            }
            
            if ( !class_exists($address->model_type) ) {
                throw new Exception(__('err_address_model_type_class_not_found'));
            }
            
            $model = $address->model_type::find( (int) $address->model_id );
            
            if ( !is_object($model) ) {
                throw new Exception(__('err_address_object_not_found'));
            }
            
            Gate::authorize('destroy', [$address, $model]);
            
            if ( !$address->delete() ) {
                throw new Exception(__('Could not delete the address!'));
            }
            
            return $this->success([
                'address' => $address
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    

    public function createOrUpdate($addressData, $model, $address_id = null)
    {
        $address = Address::find($address_id);
        if (!$address) {
            $address = new Address();
        }
        if ($addressData['geodata']['status'] == 'OK' and $addressData['geodata']['results'][0] != null) {
            $address->latitude = $addressData['geodata']['results'][0]['geometry']['location']['lat'];
            $address->longitude = $addressData['geodata']['results'][0]['geometry']['location']['lng'];
            $address->formatted_address = $addressData['geodata']['results'][0]['formatted_address'];
            $address->entered_address = $addressData['inserted'];
            $address->geo = $addressData['geodata'];

            foreach ($addressData['geodata']['results'][0]['address_components'] as $address_component) {
                if (in_array('country', $address_component['types'])) {
                    $address->country_code = $address_component['short_name'];
                    $address->country = $address_component['long_name'];
                }
                if (in_array('administrative_area_level_1', $address_component['types'])) {
                    $address->administrative_1 = $address_component['long_name'];
                }
                if (in_array('administrative_area_level_2', $address_component['types'])) {
                    $address->administrative_2 = $address_component['long_name'];
                }
                if (in_array('administrative_area_level_3', $address_component['types'])) {
                    $address->administrative_3 = $address_component['long_name'];
                }
                if (in_array('administrative_area_level_4', $address_component['types'])) {
                    $address->administrative_4 = $address_component['long_name'];
                }

                if (in_array('administrative_area_level_5', $address_component['types'])) {
                    $address->administrative_5 = $address_component['long_name'];
                }

                if (in_array('postal_code', $address_component['types'])) {
                    $address->postal_code = $address_component['long_name'];
                }

                if (in_array('route', $address_component['types'])) {
                    $address->route = $address_component['long_name'];
                }

                if (in_array('street_number', $address_component['types'])) {
                    $address->street_number = $address_component['long_name'];
                }
            }
        }

        $address->model_type = $model::class;
        $address->model_id = $model->id;
        $address->save();
    }


    /**
     * Validate address data
     *
     * @param  array       $data
     * @return void
     */
    public function validateData($data)
    {
        Validator::make($data, [
            'model_type' => [
                'required',
                'max:31'
            ],
            'model_id' => [
                'required',
                'integer'
            ],
           
            'latitude' => [
                'numeric',
                'between:-180,180'
            ],
            'longitude' => [
                'numeric',
                'between:-180,180'
            ],
            'country_code' => [
                'max:3'
            ],
            'language' => [
                'required',
                'max:5'
            ],
            'formatted_address' => [
                'max:63'
            ],
            'country' => [
                'required',
                'max:31'
            ],
            'administrative_1' => [
                'required',
                'max:31'
            ],
            'administrative_2' => [
                'max:31'
            ],
            'administrative_3' => [
                'max:31'
            ],
            'administrative_4' => [
                'max:31'
            ],
            'locality' => [
                'max:31'
            ],
            'route' => [
                'max:31'
            ],
            'street_number' => [
                'max:15'
            ],
            'postal_code' => [
                'max:15'
            ]
        ])->validate();
    }
    
    /**
     * Instantiate a new controller instance.
     *
     * @return Void
     */
    public function __construct()
    {
        parent::__construct();
    }
}
