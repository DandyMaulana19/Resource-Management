<?php

namespace App\Http\Middleware;

use App\Services\UserActivityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function __construct(
        private UserActivityService $userActivityService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Track user activity if user is authenticated
        if (Auth::check()) {
            $this->userActivityService->markUserOnline();
        }

        return $response;
    }
}
