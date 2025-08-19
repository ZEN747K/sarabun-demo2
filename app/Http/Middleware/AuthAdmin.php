<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->check()){
            $allowedEmails = ['paj01', 'paj02', 'paj03'];
            if (in_array(auth()->user()->email, $allowedEmails)) {
                auth()->user()->permission_id = 9;
            }
            return $next($request);
        }else{
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();
            return redirect('/');
        }
    }
}
