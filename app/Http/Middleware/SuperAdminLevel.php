<?php

namespace App\Http\Middleware;

use Closure;

class SuperAdminLevel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $current_user_level = $request->user()->role;
        $userLevels = config('QuestApp.UserLevels');

        if ($current_user_level === $userLevels['sa']) {
            return $next($request);
        } else {
            return ResponseHelper(config('QuestApp.JsonResponse.403'));
        }
    }
}
