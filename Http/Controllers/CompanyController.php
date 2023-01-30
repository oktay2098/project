<?php
namespace App\Http\Controllers;

use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Company;
use App\Models\Address;
use App\Http\Resources\CompanyResource;

class CompanyController extends Controller
{
    /**
     * Get all companies
     *
     * @param  Request   $request
     * @return Array
     */
    public function index(Request $request)
    {
        try {
            $query  = $request->query();
            $filter = [
                'AND' => [
                    ['deleted_at', '=', NULL]
                ],
                'OR'  => []
            ];
            $sort   = isset($query['sort']) && !empty(trim($query['sort'])) ? trim($query['sort'])            : 'name';
            $order  = isset($query['dir'])  && !empty(trim($query['dir']))  ? strtoupper(trim($query['dir'])) : 'ASC';
            $limit  = isset($query['limit'])                                ? (int) $query['limit']           : 0;
            $page   = isset($query['page'])                                 ? (int) $query['page']            : 1;
            
            if ( isset($query['s']) && !empty(trim($query['s'])) ) $filter['OR'][] = [
                ['name',  'like', '%'. trim($query['s']) . '%'],
                ['email', 'like', '%'. trim($query['s']) . '%'],
                ['web',   'like', '%'. trim($query['s']) . '%']
            ];
            
            if ( isset($query['email']) && !empty(trim($query['email'])) ) {
                $filter['AND'][] = ['email', '=', trim($query['email'])];
            }
            
            if ( isset($query['phone']) && !empty(trim($query['phone'])) ) {
                $filter['AND'][] = ['phone', '=', trim($query['phone'])];
            }
            
            if ( isset($query['web']) && !empty(trim($query['web'])) ) {
                $filter['AND'][] = ['web', '=', trim($query['web'])];
            }
            
            if ( isset($query['identity']) && !empty(trim($query['identity'])) ) {
                $filter['AND'][] = ['identity', '=', trim($query['identity'])];
            }
            
            if ( isset($query['tax_identity']) && !empty(trim($query['tax_identity'])) ) {
                $filter['AND'][] = ['tax_identity', '=', trim($query['tax_identity'])];
            }
            
            $builder = Company::where($filter['AND']);
            
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
            
            $companies = $builder
                         ->orderBy($sort, $order)
                         ->paginate($limit);
            $companies = CompanyResource::collection($companies)->response()->getData(true);
            
            return $this->success([
                'resource' => $companies
            ]);
        
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Show company
     *
     * @param  INT       $id
     * @param  Request   $request
     * @return Array
     */
    public function show(int $id, Request $request)
    {
        try {
            $company = Company::find($id);
            
            if ( !is_object($company) ) {
                throw new Exception(__('Could not find company with this id!'));
            }
            
            $data               = $company->toArray();
            $data['created_at'] = $company->created_at ? $company->created_at->format('d/m/Y H:i:s') : null;
            $data['updated_at'] = $company->updated_at ? $company->updated_at->format('d/m/Y H:i:s') : null;
            $data['starter']    = $company->starter;
            $data['users']      = $company->users;
            
            unset($data['deleted_at']);
            
            return $this->success([
                'company' => $data
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Update company
     *
     * @param  INT       $id
     * @param  Request   $request
     * @return Array
     */
    public function update(int $id, Request $request)
    {
        try {
            if ( !$request->isJson() || !$request->accepts(['application/json']) ) {
                throw new Exception(__('err_invalid_request'));
            }
            
            $company = Company::find($id);
            
            if ( !is_object($company) ) {
                throw new Exception(__('Could not find company with this id!'));
            }
            
            $starter = User::find( (int) $company->starter_id );
            
            if ( !is_object($starter) ) {
                throw new Exception(__('Could not find user with the company starter id!'));
            }
            
            if ( !Auth::check() || ( (int) Auth::user()->id != (int) $starter->id && !Auth::user()->can('update companies')) ) {
                throw new Exception(__('You are not allowed to update a company!'));
            }
            
            $data = (array) $request->input('data');
            
            unset($data['starter_id']);
            
            $company->fill( $data );
            $this->validateData(array_filter($company->toArray(), function($v, $k) {
                return !empty($v);
                
            }, ARRAY_FILTER_USE_BOTH), true);
            
            if ( !$company->update() ) {
                throw new Exception(__('Could not update the company!'));
            }
            
            return $this->success([
                'company' => $company
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Store company
     *
     * @param  Request   $request
     * @return Array
     */
    public function store(Request $request)
    {
        try {
            if ( !$request->isJson() || !$request->accepts(['application/json']) ) {
                throw new Exception(__('err_invalid_request'));
            }
            
            $data = (array) $request->input('data');
            
            $this->validateData($data);
            
            $starter = User::find( (int) $data['starter_id'] );
            
            if ( !is_object($starter) ) {
                throw new Exception(__('Could not find user with the given company starter id!'));
            }
            
            if ( Auth::check() && (int) Auth::user()->id != (int) $starter->id && !Auth::user()->can('create companies') ) {
                throw new Exception(__('You are not allowed to create a new company!'));
            }
            
            $company = Company::create($data);
            
            if ( !$company ) {
                throw new Exception(__('Could not create the company!'));
            }
            
            $starter->company_id = $company->id;
            
            $starter->update();
            
            if ( isset($data['address']) && count($data['address']) > 0 ) {
                $address_data               = $data['address'];
                $address_data['model_type'] = 'App\\Models\\Company';
                $address_data['model_id']   = $company->id;
                
                (new AddressController())->validateData($address_data);
                
                Address::create($address_data);
            }
            
            return $this->success([
                'company' => $company
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Delete company
     *
     * @param  INT       $id
     * @param  Request   $request
     * @return Array
     */
    public function destroy(int $id, Request $request)
    {
        try {
            if ( !Auth::check() || !Auth::user()->can('access admin') || !Auth::user()->can('delete companies') ) {
                throw new Exception(__('You are not allowed to delete a company!'));
            }
            
            $company = Company::find($id);
            
            if ( !is_object($company) ) {
                throw new Exception(__('Could not find company with this id!'));
            }
            
            $users = User::where('company_id', $company->id)->get();
            
            if ( count($users) ) {
                foreach ( $users as $user ) {
                    $user->company_id = null;
                    
                    $user->update();
                }
            }
            
            if ( !$company->delete() ) {
                throw new Exception(__('Could not delete the company!'));
            }
            
            return $this->success([
                'company' => $company
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Validate company data
     *
     * @param  array       $data
     * @param  bool        $update
     * @return void
     */
    public function validateData($data, $update = false)
    {
        Validator::make($data, [
            'starter_id' => [
                'required',
                'integer'
            ],
            'name' => [
                'required',
                'max:63'
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:63',
                !$update ? 'unique:App\Models\Company' : ''
            ],
            'phone' => [
                'max:15',
                !$update ? 'unique:App\Models\Company' : ''
            ],
            'web' => [
                'regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
                'max:127'
            ],
            'identity' => [
                'max:25'
            ],
            'tax_identity' => [
                'max:25'
            ]
        ])->validate();
    }

    
    public function updateCompanyImage(Request $request)
    {
        $company = $this->user()?->company;

        if ($company) {
            (new MediaManagerController)->deleteAllTheModelMedia($company);
            
            $company->addAllMediaFromTokens();
    
            return [
                'status' => true, 
                'company' => $company
            ];
        } else {
            return response([
                'message' => "you don't have a company"
            ], 403);
        }
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
