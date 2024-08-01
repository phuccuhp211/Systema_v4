<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Midd_Rspw_exp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('is_mail')) {
            $sessionData = session('is_mail');

            if (isset($sessionData['timestamp']) && (time() - $sessionData['timestamp']) > 600) {
                session()->forget('is_mail');
            }
        }

        return $next($request);
    }
}
