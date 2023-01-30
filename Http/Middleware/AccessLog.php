<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AccessLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        if ( $request->route()->getName() === 'passport.token' && isset($request->grant_type) && $request->grant_type == 'password' ) {
            $data = json_decode($response->getContent());
            
            if ( is_object($data) && isset($data->access_token) && !empty($data->access_token) ) {
                $user = User::where('email', $request->username)->first();
                
                $user->loggedin_at = date('Y-m-d H:i:s');
                
                $user->save();
            }
        }
        
        return $response;
    }
}
