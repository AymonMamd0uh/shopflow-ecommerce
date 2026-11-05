<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * Only authenticated users with the admin role
     * are allowed to access protected admin routes.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (
            !$request->user() ||
            $request->user()->role !== 'admin'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
