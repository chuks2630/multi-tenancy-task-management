<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;

// Stripe Webhooks (public endpoint)
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe'])
    ->name('webhooks.stripe');

// Public API routes
Route::prefix('v1')->group(function () {
    
    // Plans
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/plans/{plan}', [PlanController::class, 'show']);
    
    // Tenant Registration
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::get('/tenants/check-subdomain/{subdomain}', [TenantController::class, 'checkSubdomain']);
    
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Tenant Management
        Route::get('/tenants/{tenant}', [TenantController::class, 'show']);
        Route::put('/tenants/{tenant}', [TenantController::class, 'update']);
        Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy']);
        
        // Subscription Management
        Route::prefix('tenants/{tenant}/subscription')->group(function () {
            Route::get('/', [SubscriptionController::class, 'show']);
            Route::post('/checkout', [SubscriptionController::class, 'createCheckoutSession']);
            Route::post('/portal', [SubscriptionController::class, 'createPortalSession']);
            Route::post('/change-plan', [SubscriptionController::class, 'changePlan']);
            Route::post('/cancel', [SubscriptionController::class, 'cancel']);
        });
        
        // Admin routes (optional)
        Route::middleware('admin')->group(function () {
            Route::post('/plans', [PlanController::class, 'store']);
            Route::put('/plans/{plan}', [PlanController::class, 'update']);
            Route::delete('/plans/{plan}', [PlanController::class, 'destroy']);
        });
    });
});