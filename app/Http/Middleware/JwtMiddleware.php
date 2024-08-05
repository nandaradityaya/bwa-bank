<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

     // handle middleware
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate(); // cek tokennya valid atau tidak
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['message' => 'Token Invalid'], 401); // jika token invalid
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(['message' => 'Token Expired'], 401); // jika token expired
            }else{
                return response()->json(['message' => 'Authorization Token Not Found'], 500); // jika token tidak ditemukan
            }
        }
        return $next($request);
    }
}
