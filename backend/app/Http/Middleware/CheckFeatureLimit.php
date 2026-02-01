<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;
use App\Services\StripeService;

class CheckFeatureLimit
{
    public function __construct(
        private StripeService $stripeService
    ) {}

    public function handle(Request $request, Closure $next, string $feature)
    {
        $tenant = tenancy()->tenant;

        if (!$tenant) {
            return ApiResponse::error('Tenant not identified', null, 400);
        }

        if (!$this->stripeService->checkFeatureLimit($tenant, $feature)) {
            $plan = $tenant->plan;
            $limit = $plan->getFeatureLimit($feature);

            return ApiResponse::error(
                "You've reached your plan limit of {$limit} for {$feature}. Please upgrade your plan.",
                ['feature' => $feature, 'limit' => $limit],
                403
            );
        }

        return $next($request);
    }
}