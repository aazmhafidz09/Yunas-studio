<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();
        
        foreach($roles as $role) {
            if($role === 'Super Admin' && $user->role_id == 1) {
                return $next($request);
            }
            if($role === 'Admin' && $user->role_id == 2) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized access');
    }
}