<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Coordinates
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->has(['latitude', 'longitude', 'countryId'])
            || empty($request->query('latitude'))
            || empty($request->query('longitude'))
            || empty($request->query('countryId'))){
            return response()->json(['message' => 'failed, provide the coordinates!', 'success' => false, 'description' => '', 'code' => 400, 'data' => null], 400);
        }else{
            return $next($request);
        }
    }
}
