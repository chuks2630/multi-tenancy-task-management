<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!$request->user()->hasPermissionTo($permission)) {
            return ApiResponse::forbidden(
                "You don't have permission to {$permission}"
            );
        }

        return $next($request);
    }
}