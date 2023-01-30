<?php

namespace App\Http\Controllers;

use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Client;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use App\Models\User;
use App\Models\Address;
use App\Http\Resources\UserResource;
//traits
use App\Traits\ApiResponserTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

class UserController extends Controller
{
    use ApiResponserTrait;
    /**
     * Logout from all devices
     *
     * @param  Request   $request
     * @return Array
     */
    public function logoutAll(Request $request)
    {
        try {
            $tokens = Auth::user()->tokens->pluck('id')->toArray();

            Token::whereIn('id', $tokens)->update(['revoked' => 1]);
            RefreshToken::whereIn('access_token_id', $tokens)->update(['revoked' => 1]);

            return $this->success([]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Logout
     *
     * @param  Request   $request
     * @return Array
     */
    public function logout(Request $request)
    {
        try {
            $token = Auth::user()->token();

            $token->revoke();

            return $this->success([]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Refresh
     *
     * @param  Request   $request
     * @return Array
     */
    public function refresh(Request $request)
    {
        try {
            $request->validate([
                'refresh_token' => 'required'
            ]);

            $user   = $request->getUser();
            $client = Client::where('password_client', 1)->first();

            if (!is_object($client)) {
                throw new Exception(__('Can not find any password grant client!'));
            }

            $response = Http::asForm()->post(App::make('url')->to('/') . '/oauth/token', [
                'grant_type'    => 'refresh_token',
                'client_id'     => $client->id,
                'client_secret' => config('auth.clients.password.secret'),
                'refresh_token' => $request->refresh_token,
                'scope'         => ''
            ]);
            $result   = $response->json();

            if (!isset($result['access_token']) || empty($result['access_token'])) {
                throw new Exception(isset($result['message']) ? $result['message'] : 'Invalid Data!');
            }

            return $this->success([
                'token' => $response->json(),
                'user'  => $user
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Login By Facebook
     *
     * @param  Request   $request
     * @return Array
     */
    public function loginFacebook(Request $request)
    {
        try {
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Login By Google
     *
     * @param  Request   $request
     * @return Array
     */
    public function loginGoogle(Request $request)
    {
        try {
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Login
     *
     * @param  Request   $request
     * @return Array
     */

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required'
            ]);

            $client = Client::where('password_client', 1)->first();

            if (!is_object($client)) {
                throw new Exception(__('Can not find any password grant client!'));
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                throw new Exception(__('There is not any registered user with this account email!'));
            }

            if ((int) $user->status < 1 || $user->deleted_at !== null) {
                throw new Exception(__('You are not allowed to access this resource!'));
            }

            $response = Http::asForm()->post(App::make('url')->to('/') . '/oauth/token', [
                'grant_type'    => 'password',
                'client_id'     => $client->id,
                'client_secret' => config('auth.clients.password.secret'),
                'username'      => $request->email,
                'password'      => $request->password,
                'scope'         => ''
            ]);

            $result   = $response->json();

            if (!isset($result['access_token']) || empty($result['access_token'])) {
                throw new Exception(isset($result['message']) ? $result['message'] : 'The user credentials were incorrect.');
            }

            return $this->success([
                'token' => $response->json(),
                'user'  => $user
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Register
     *
     * @param  Request   $request
     * @return Array
     */
    /* public function register(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required',
                'roles' => 'required',
                'last_name' => 'required',
                'phone' => 'required|unique:users',
                'email'    => 'required|email|unique:users',
                'password' => 'required',
                'password_again' => 'required'
            ]);

            if ($request->password != $request->password_again) {
                throw new Exception(__('Passwords Do Not Match !'));
            }

            $user = new User();
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->status = 1;
            $user->password = Hash::make($request->password);
            if ($user->save()) {
                $user->syncRoles($request->roles);
            }

            return $this->success([
                'roles' => $request->roles,
                'user'  => $user
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    } */

    public function register(Request $request)
    {

        $data    = (array) $request->input('data');
        $data['language'] = isset($data['language']) && !empty(trim($data['language'])) ? trim($data['language']) : App::currentLocale();

        $validator = Validator::make($data, [
            'first_name' => [
                'required',
                'max:63'
            ],
            'last_name' => [
                'max:63'
            ],
            'user_type' => [
                'required_unless:user_type,customer',
                'max:90'
            ],
            'service_type' => [
                'nullable',
                'max:90'
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'unique:users,email',
                'max:63',
            ],
            'phone' => [
                'max:15',
                'unique:users,phone',
            ],
            'password' => [
                'confirmed',
                'min:6'
            ],
            'address' => [
                'required_unless:user_type,customer',
                'array'
            ],
            'address.country' => [
                'required',
                'string'
            ],
            'address.country_code' => [
                'required',
                'string',
                'min:3'
            ],
            'address.province' => [
                'required',
                'string',
            ],
            'address.city' => [
                'required',
                'string',
            ],
            'address.neighborhood' => [
                'required',
                'string',
            ],
            'address.postal_code' => [
                'nullable',
                'string',
            ],
            'status' => [
                'in:0, 1'
            ]
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('user_validation', $validator->errors()->all(), 409);
        }

        $data['password'] = Hash::make($data['password']);
        $user             = User::create($data);

        if (!$user) {
            return $this->errorResponse('register_user', 'Could not create the user!', 409);
        }

        if (isset($data['address'])) {
            Address::create([
                'model_type' => User::class,
                'model_id' => $user->id,
                'country' => $data['address']['country'],
                'country_code' => $data['address']['country_code'],
                'administrative_1' => $data['address']['province'],
                'administrative_2' => $data['address']['city'],
                'administrative_3' => $data['address']['neighborhood'],
                'postal_code' => $data['address']['postal_code'] ?? null,
            ]);
        }

        $role = null;
        if ($data['user_type'] == 'customer') {
            $role = 'customer';
        } elseif ($data['user_type'] == 'corporate' && $data['service_type'] == 'cars') {
            $role = 'cars_corporate';
        } elseif ($data['user_type'] == 'corporate' && $data['service_type'] == 'properties') {
            $role = 'properties_corporate';
        } elseif ($data['user_type'] == 'agent' && $data['service_type'] == 'cars') {
            $role = 'cars_agent';
        } elseif ($data['user_type'] == 'agent' && $data['service_type'] == 'properties') {
            $role = 'properties_agent';
        }

        $user->assignRole($role);

        event(new Registered($user));

        return $this->successResponse($user, 'register_user', 'User ' . $user->id . ' created');
    }


    /**
     * Show user all permissions
     *
     * @param  INT        $id
     * @param  Request    $request
     * @return Array
     */
    public function allPermissions(int $id, Request $request)
    {
        try {
            if (!Auth::check() || !Auth::user()->can('access admin')) {
                throw new Exception(__('You are not allowed to access this resource!'));
            }

            $user = User::find($id);

            if (!is_object($user)) {
                throw new Exception(__('Could not find user with this id!'));
            }

            $permissions = $user->getAllPermissions()->pluck('name');

            return $this->success([
                'permissions' => $permissions,
                'user'        => $user
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Show user all roles
     *
     * @param  INT        $id
     * @param  Request    $request
     * @return Array
     */
    public function allRoles(int $id, Request $request)
    {
        try {
            if (!Auth::check() || !Auth::user()->can('access admin')) {
                throw new Exception(__('You are not allowed to access this resource!'));
            }

            $user = User::find($id);

            if (!is_object($user)) {
                throw new Exception(__('Could not find user with this id!'));
            }

            $roles = $user->getRoleNames();

            return $this->success([
                'roles' => $roles,
                'user'  => $user
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Assign a role to a user
     *
     * @param  INT       $id
     * @param  Request   $request
     * @return Array
     */
    public function assignRole(int $id, Request $request)
    {
        try {
            if (!$request->isJson() || !$request->accepts(['application/json'])) {
                throw new Exception(__('err_invalid_request'));
            }

            if (!Auth::check() || !Auth::user()->can('access admin') || !Auth::user()->can('assign user roles')) {
                throw new Exception(__('You are not allowed to assign a role to a user!'));
            }

            $user = User::find($id);

            if (!is_object($user)) {
                throw new Exception(__('Could not find user with this id!'));
            }

            $role = $request->input('role');

            if (empty(trim($role))) {
                throw new Exception(__('No role given to be assigned!'));
            }

            $user->assignRole($role);

            return $this->success([
                'user' => $user
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Assign a role to a user
     *
     * @param  INT       $id
     * @param  Request   $request
     * @return Array
     */
    public function removeRole(int $id, Request $request)
    {
        try {
            if (!$request->isJson() || !$request->accepts(['application/json'])) {
                throw new Exception(__('err_invalid_request'));
            }

            if (!Auth::check() || !Auth::user()->can('access admin') || !Auth::user()->can('remove user roles')) {
                throw new Exception(__('You are not allowed to remove a role from a user!'));
            }

            $user = User::find($id);

            if (!is_object($user)) {
                throw new Exception(__('Could not find user with this id!'));
            }

            $role = $request->input('role');

            if (empty(trim($role))) {
                throw new Exception(__('No role given to be removed!'));
            }

            $user->removeRole($role);

            return $this->success([
                'user' => $user
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Get all users
     *
     * @param  Request   $request
     * @return Array
     */
    public function index(Request $request)
    {
        try {
            if (!Auth::check() || !Auth::user()->can('access admin')) {
                throw new Exception(__('You are not allowed to access this resource!'));
            }

            $query  = $request->query();
            $filter = [
                'AND' => [
                    ['deleted_at', '=', NULL]
                ],
                'OR'  => []
            ];
            $sort   = isset($query['sort']) && !empty(trim($query['sort'])) ? trim($query['sort'])            : 'first_name';
            $order  = isset($query['dir'])  && !empty(trim($query['dir']))  ? strtoupper(trim($query['dir'])) : 'ASC';
            $limit  = isset($query['limit'])                                ? (int) $query['limit']           : 0;
            $page   = isset($query['page'])                                 ? (int) $query['page']            : 1;

            if (isset($query['s']) && !empty(trim($query['s']))) $filter['OR'][] = [
                ['first_name', 'like', '%' . trim($query['s']) . '%'],
                ['last_name',  'like', '%' . trim($query['s']) . '%'],
                ['email',      'like', '%' . trim($query['s']) . '%']
            ];

            if (isset($query['status']) && in_array(trim($query['status']), ['y', 'n'])) {
                if (trim($query['status']) == 'y') {
                    $filter['AND'][] = ['status', '=', 1];
                } else {
                    $filter['AND'][] = ['status', '=', 0];
                }
            }

            $builder = User::where($filter['AND']);

            if (count($filter['OR'])) {
                foreach ($filter['OR'] as $or) {
                    if (count($or) > 1) {
                        $builder->where(function ($q) use ($or) {
                            $q->where([$or[0]]);

                            for ($i = 1; $i < count($or); $i++) {
                                $q->orWhere([$or[$i]]);
                            }
                        });
                    }
                }
            }

            $users = $builder
                ->orderBy($sort, $order)
                ->paginate($limit);
            $users = UserResource::collection($users)->response()->getData(true);

            return $this->success([
                'resource' => $users
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Show user
     *
     * @param  INT       $id
     * @param  Request   $request
     * @return Array
     */
    public function show(int $id, Request $request)
    {
        try {
            $user = User::find($id);

            if (!is_object($user)) {
                throw new Exception(__('Could not find user with this id!'));
            }

            if (!Auth::check() || ((int) $user->id != (int) Auth::user()->id && !Auth::user()->can('access admin'))) {
                throw new Exception(__('You are not allowed to access this resource!'));
            }

            $data                      = $user->toArray();
            $data['loggedin_at']       = $user->loggedin_at       ? $user->loggedin_at->format('d/m/Y H:i:s')       : null;
            $data['email_verified_at'] = $user->email_verified_at ? $user->email_verified_at->format('d/m/Y H:i:s') : null;
            $data['created_at']        = $user->created_at        ? $user->created_at->format('d/m/Y H:i:s')        : null;
            $data['updated_at']        = $user->updated_at        ? $user->updated_at->format('d/m/Y H:i:s')        : null;
            $data['company']           = $user->company;
            $data['company_started']   = $user->companyStarted;

            unset($data['deleted_at']);

            return $this->success([
                'user' => $data
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Update the authenticated user
     *
     * @param  Request   $request
     * @return Array
     */
    public function updateAuth(Request $request)
    {
        return $this->update(0, $request);
    }

    /**
     * Update user
     *
     * @param  INT       $id
     * @param  Request   $request
     * @return Array
     */
    public function update(int $id = 0, Request $request)
    {
        try {
            if (!$request->isJson() || !$request->accepts(['application/json'])) {
                throw new Exception(__('err_invalid_request'));
            }

            if ($id < 1) {
                $user = Auth::user();
            } else {
                $user = User::find($id);

                if (!is_object($user)) {
                    throw new Exception(__('Could not find user with this id!'));
                }
            }

            if (!Auth::check() || ((int) $user->id != (int) Auth::user()->id && !Auth::user()->can('update users'))) {
                throw new Exception(__('You are not allowed to update a user!'));
            }

            if ((int) $user->id != (int) Auth::user()->id && $user->hasRole('Super Admin')) {
                throw new Exception(__('A user with the role Super Admin cannot be updated!'));
            }

            $data = (array) $request->input('data');

            Validator::make($data, [
                'password' => [
                    'confirmed',
                    'min:6'
                ]
            ])->validate();

            if (isset($data['password']) && !empty(trim($data['password']))) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $user->fill($data);
            $this->validateData(array_filter($user->toArray(), function ($v, $k) {
                return !empty($v);
            }, ARRAY_FILTER_USE_BOTH), true);

            if (isset($data['address'])) {
                
                Address::updateOrCreate([
                    'id' => $data['address']['id'] ?? null
                ],[
                    'model_type' => User::class,
                    'model_id' => $user->id,
                    'country' => $data['address']['country'],
                    'country_code' => $data['address']['country_code'],
                    'administrative_1' => $data['address']['province'],
                    'administrative_2' => $data['address']['city'],
                    'administrative_3' => $data['address']['neighborhood'],
                    'postal_code' => $data['address']['postal_code'] ?? null,
                ]);
            }


            if (!$user->update()) {
                throw new Exception(__('Could not update the user!'));
            }

            return $this->success([
                'user' => $user
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Store user
     *
     * @param  Request   $request
     * @return Array
     */
    public function store(Request $request)
    {
        try {
            if (!$request->isJson() || !$request->accepts(['application/json'])) {
                throw new Exception(__('err_invalid_request'));
            }

            if (Auth::check() && (!Auth::user()->can('access admin') || !Auth::user()->can('create users'))) {
                throw new Exception(__('You are not allowed to create a new user!'));
            }

            $data    = (array) $request->input('data');
            $data['language'] = isset($data['language']) && !empty(trim($data['language'])) ? trim($data['language']) : App::currentLocale();

            $this->validateData($data);

            $data['password'] = Hash::make($data['password']);
            $user             = User::create($data);

            if (!$user) {
                throw new Exception(__('Could not create the user!'));
            }

            if (isset($data['address']) && count($data['address']) > 0) {
                $address_data               = $data['address'];
                $address_data['model_type'] = 'App\\Models\\User';
                $address_data['model_id']   = $user->id;

                (new AddressController())->validateData($address_data);

                Address::create($address_data);
            }


            event(new Registered($user));

            return $this->success([
                'user' => $user,
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Delete user
     *
     * @param  INT       $id
     * @param  Request   $request
     * @return Array
     */
    public function destroy(int $id, Request $request)
    {
        try {
            if (!Auth::check() || !Auth::user()->can('access admin') || !Auth::user()->can('delete users')) {
                throw new Exception(__('You are not allowed to delete a user!'));
            }

            $user = User::find($id);

            if (!is_object($user)) {
                throw new Exception(__('Could not find user with this id!'));
            }

            if ($user->hasRole('Super Admin')) {
                throw new Exception(__('A user with the role Super Admin cannot be deleted!'));
            }

            if (!$user->delete()) {
                throw new Exception(__('Could not delete the user!'));
            }

            return $this->success([
                'user' => $user
            ]);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Validate user data
     *
     * @param  array       $data
     * @param  bool        $update
     * @return void
     */
    public function validateData($data, $update = false)
    {
        Validator::make($data, [
            'first_name' => [
                'required',
                'max:63'
            ],
            'last_name' => [
                'max:63'
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:63',
                !$update ? 'unique:App\Models\User' : ''
            ],
            'phone' => [
                'max:15',
                !$update ? 'unique:App\Models\User' : ''
            ],
            'password' => [
                !$update ? 'required' : '',
                'confirmed',
                'min:6'
            ],
            'status' => [
                'in:0, 1'
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



    // Email verification 
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request)
    {
        $user = User::find($request->id);
        if (!hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException();
        }

        if ($user->hasVerifiedEmail()) {
            return $request->wantsJson()
                ? response([], 204)
                : redirect($this->verifiedRedirectPath());
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $request->wantsJson()
            ? response([
                'verified' => true
            ], 204)
            : redirect($this->verifiedRedirectPath())->with('verified', true);
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $request->wantsJson()
                ? response([
                    'verified' => true
                ], 204)
                : redirect($this->verifiedRedirectPath());
        }

        $request->user()->sendEmailVerificationNotification();

        return $request->wantsJson()
            ? response([
                'resent' => true
            ], 202)
            : back()->with('resent', true);
    }

    public function verifiedRedirectPath()
    {
        return env('FRONT_URL') . '/email/verify/success';
    }

    public function updateUserImage(Request $request)
    {
        $user = $this->user();
        (new MediaManagerController)->deleteAllTheModelMedia($user);

        $user->addAllMediaFromTokens();

        return [
            'status' => true, 
            'user' => $user
        ];
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
}
