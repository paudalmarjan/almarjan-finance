<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParentPortalMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('parent_student_id')) {
            return redirect()->route('wali.login')->with('error', 'Silakan masuk menggunakan NIS dan PIN terlebih dahulu.');
        }

        return $next($request);
    }
}
