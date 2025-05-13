<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TSecretaryMiddleware
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
        if (Auth::check() && Auth::user()->role === 'tsecretary') {
            // Check if the user has been assigned to an event
            $user = Auth::user();
            if (!$user->intrams_id || !$user->event_id) {
                return response()->json([
                    'message' => 'You are not assigned to any event. Please contact the administrator.'
                ], 403);
            }
            
            return $next($request);
        }

        return response()->json([
            'message' => 'Unauthorized. Technical Secretary access required.'
        ], 403);
    }
}