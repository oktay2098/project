<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CatchCurrencyMiddleware
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
        $headerCurrency = $request->header('currency');
        $appCurrency = config('app.currency');

        if ($headerCurrency && strtoupper($headerCurrency) != $appCurrency) {
            config(['app.currency' => strtoupper($headerCurrency)]);
        }

        
        return $next($request);
    }
}
