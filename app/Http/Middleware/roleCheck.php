<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;

class roleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        $role_id = $request->user()->role_id;
        $role_name = Role::find($role_id)->name;

        if ($role_name != $role) {
            
            return response()->json('You dont have permission for make this action', 422);

        }

        return $next($request);
    }
}
