<?php
namespace App\Http\Controllers;

use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleController extends Controller
{    
    /**
     * Get all roles
     *
     * @param  Request   $request
     * @return Array
     */
    public function index(Request $request)
    {
      

        try {
            if ( !Auth::check() || !Auth::user()->can('access admin') ) {
                throw new Exception(__('You are not allowed to access this resource!'));
            }
            
            return $this->success([
                'roles' => Role::all()->pluck('name')
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Get all permissions
     *
     * @param  Request   $request
     * @return Array
     */
    public function allPermissions(Request $request)
    {
        try {
            if ( !Auth::check() || !Auth::user()->can('access admin') ) {
                throw new Exception(__('You are not allowed to access this resource!'));
            }
            
            return $this->success([
                'permissions' => Permission::all()->pluck('name')
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Get all permissions
     *
     * @param  String    $name
     * @param  Request   $request
     * @return Array
     */
    public function permissions(String $name, Request $request)
    {
        try {
            if ( !Auth::check() || !Auth::user()->can('access admin') ) {
                throw new Exception(__('You are not allowed to access this resource!'));
            }
            
            $role = Role::findByName($name);
            
            if ( !is_object($role) ) {
                throw new Exception(__('Could not find role with this name!'));
            }
            
            $permissions = $role->permissions->pluck('name');
            
            return $this->success([
                'permissions' => $permissions,
                'role'        => $role
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Give permission to a role
     *
     * @param  String    $name
     * @param  Request   $request
     * @return Array
     */
    public function givePermissionTo(String $name, Request $request)
    {
        try {
            if ( !$request->isJson() || !$request->accepts(['application/json']) ) {
                throw new Exception(__('err_invalid_request'));
            }
            
            if ( !Auth::check() || !Auth::user()->can('access admin') || !Auth::user()->hasRole('Super Admin') ) {
                throw new Exception(__('You are not allowed to give a permission to a role!'));
            }
            
            $role = Role::findByName($name);
            
            if ( !is_object($role) ) {
                throw new Exception(__('Could not find role with this name!'));
            }
            
            $permission = $request->input('permission');
            
            if ( empty(trim($permission)) ) {
                throw new Exception(__('No permission given to be assigned!'));
            }
            
            $prmsn = Permission::where('name', $permission)->first();
            
            if ( !is_object($prmsn) ) {
                $permission = Permission::create(['name' => $permission]);
            }
            
            $role->givePermissionTo($permission);
            
            return $this->success([
                'role' => $role
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Revoke permission to a role
     *
     * @param  String    $name
     * @param  Request   $request
     * @return Array
     */
    public function revokePermissionTo(String $name, Request $request)
    {
        try {
            if ( !$request->isJson() || !$request->accepts(['application/json']) ) {
                throw new Exception(__('err_invalid_request'));
            }
            
            if ( !Auth::check() || !Auth::user()->can('access admin') || !Auth::user()->hasRole('Super Admin') ) {
                throw new Exception(__('You are not allowed to revoke a permission to a role!'));
            }
            
            $role = Role::findByName($name);
            
            if ( !is_object($role) ) {
                throw new Exception(__('Could not find role with this name!'));
            }
            
            $permission = $request->input('permission');
            
            if ( empty(trim($permission)) ) {
                throw new Exception(__('No permission given to be assigned!'));
            }
            
            $role->revokePermissionTo($permission);
            
            return $this->success([
                'role' => $role
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
        
    }
    
    /**
     * Create a new role
     *
     * @param  Request   $request
     * @return Array
     */
    public function create(Request $request)
    {
        try {
            if ( !$request->isJson() || !$request->accepts(['application/json']) ) {
                throw new Exception(__('err_invalid_request'));
            }
            
            $data = (array) $request->input('data');
            
            Validator::make($data, [
                'name' => [
                    'required',
                    'max:63'
                ],
                'guard_name' => [
                    'max:63'
                ]
            ])->validate();
            
            if ( !Auth::check() || !Auth::user()->can('access admin') || !Auth::user()->hasRole('Super Admin') ) {
                $this->checkSuperAdmin(Auth::user());
            }
            
            $role = Role::create($data);
            
            if ( !$role ) {
                throw new Exception(__('Could not create the role!'));
            }
            
            return $this->success([
                'role' => $role
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Delete permission
     *
     * @param  String    $name
     * @param  Request   $request
     * @return Array
     */
    public function destroyPermission(String $name, Request $request)
    {
        try {
            if ( !Auth::check() || !Auth::user()->can('access admin') || !Auth::user()->hasRole('Super Admin') ) {
                throw new Exception(__('You are not allowed to delete a permission!'));
            }
            
            $permission = Permission::findByName($name);
            
            if ( !is_object($permission) ) {
                throw new Exception(__('Could not find permission with this name!'));
            }
            
            if ( !$permission->delete() ) {
                throw new Exception(__('Could not delete the permission!'));
            }
            
            return $this->success([
                'permission' => $permission
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }  
    
    /**
     * Delete role
     *
     * @param  String    $name
     * @param  Request   $request
     * @return Array
     */
    public function destroy(String $name, Request $request)
    {
        try {
            if ( !Auth::check() || !Auth::user()->can('access admin') || !Auth::user()->hasRole('Super Admin') ) {
                throw new Exception(__('You are not allowed to delete a role!'));
            }
            
            $role = Role::findByName($name);
            
            if ( !is_object($role) ) {
                throw new Exception(__('Could not find role with this name!'));
            }
            
            if ( $name == 'Super Admin' ) {
                throw new Exception(__('You are trying to delete Super Admin role!'));
            }
            
            if ( !$role->delete() ) {
                throw new Exception(__('Could not delete the role!'));
            }
            
            return $this->success([
                'role' => $role
            ]);
            
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }
    
    /**
     * Check Super Admin
     *
     * @param  App\Models\User     $user
     * @return Void
     */
    private function checkSuperAdmin($user) 
    {
        if ( !is_object($user) ) {
            throw new Exception(__('You are not allowed to do this action!'));
        }
        
        if ( !in_array( 'Super Admin', Role::all()->pluck('name')->toArray() ) ) {
            $role = Role::create([
                'name' => 'Super Admin'
            ]);
        }
        
        $supers = User::role('Super Admin')->get(); // Returns only users with the role 'Super Admin'
        
        if ( count($supers) < 1 ) {
            $user->assignRole('Super Admin');
            
        } else {
            throw new Exception(__('You are not allowed to do this action!'));
        }
    }
}
