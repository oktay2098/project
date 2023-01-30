<?php
namespace App\Http\Controllers;

use App\Exceptions\WantJSONException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;    
    
    /**
     * Instantiate a new controller instance.
     *
     * @return Void
     */
    public function __construct()
    {
        $this->middleware('set_locale');
    }
    
    /**
     * Failed Response
     *
     * @param  \Exception      $e
     * @return Array
     */
    protected function failed($e)
    {
        return [
            'status'  => false,
            'message' => $e->getMessage(),
            'errors'  => method_exists($e, 'errors') ? $e->errors() : [],
            'trace'   => $e->getTrace()
        ];
    }        
    
    /**
     * Success Response
     *
     * @param  Array      $data
     * @return Array
     */
    protected function success($data)
    {
        return ['status'  => true] + $data;
    }
    
    /**
     * Check the request content type if it is JSON or not
     *
     */
    protected function checkJSON()
    {
        if(!request()->acceptsJson() || !request()->isJson()){
            throw new WantJSONException();
        }
    }
       
    public function currentUserId()
    {
        return $this->user()->id;
    }

    public function user()
    {
        return Auth::guard()->user() ?? auth('api')->user();
    }
}
