<?php

namespace App\Http\Middleware;

use Closure;

class TeacherLevel
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
            $allowed_user_levels = [
                $userLevels['t'],
            ];

            if (in_array($current_user_level, $allowed_user_levels)) {
                return $next($request);
            } else {
                return ResponseHelper(config('QuestApp.JsonResponse.403'));
            }
        }
    }
}
