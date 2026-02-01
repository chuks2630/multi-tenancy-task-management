<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\Tenant\AuthController;
use App\Http\Controllers\Tenant\Api\DashboardController;
use App\Http\Controllers\Tenant\Api\TeamController;
use App\Http\Controllers\Tenant\Api\InvitationController;
use App\Http\Controllers\Tenant\Api\BoardController;
use App\Http\Controllers\Tenant\Api\TaskController;
use App\Http\Controllers\Tenant\Api\RoleController;
use App\Http\Controllers\Tenant\Api\AnalyticsController;
use App\Http\Controllers\Tenant\Api\NotificationController;
use App\Http\Controllers\Tenant\Api\AdminController;
use App\Http\Controllers\Tenant\Api\BillingController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

//api routes for tenants
   Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Tenant API is active',
        'tenant' => [
            'id' => tenant('id'),
            'name' => tenant('name'),
        ],
    ]);
});


// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/my-tasks', [DashboardController::class, 'myTasks']);
    });


    // Teams
    Route::apiResource('teams', TeamController::class)->only(['store'])
    ->middleware(['feature.limit:max_teams','permission:create teams']);

    Route::apiResource('teams', TeamController::class)->except(['store']);

    Route::prefix('teams/{team}')->group(function () {
        Route::get('/members', [TeamController::class, 'members']);
        Route::post('/members', [TeamController::class, 'addMember']);
        Route::delete('/members', [TeamController::class, 'removeMember']);
        Route::put('/members/role', [TeamController::class, 'updateMemberRole']);
    });

    // Invitations
    Route::prefix('invitations')->group(function () {
        Route::get('/', [InvitationController::class, 'index']);
        Route::get('/pending', [InvitationController::class, 'pending']);
        Route::post('/', [InvitationController::class, 'store']);
        Route::post('/{token}/accept', [InvitationController::class, 'accept']);
        Route::post('/{token}/reject', [InvitationController::class, 'reject']);
        Route::delete('/{invitation}', [InvitationController::class, 'cancel']);
    });

    // Boards
    Route::apiResource('boards', BoardController::class)->only(['store'])
    ->middleware(['feature.limit:max_boards','permission:create boards']);

    Route::apiResource('boards', BoardController::class)->except(['store']);

    Route::prefix('boards/{board}')->group(function () {
        Route::get('/statistics', [BoardController::class, 'statistics']);
        Route::post('/members', [BoardController::class, 'addMember']);
        Route::delete('/members', [BoardController::class, 'removeMember']);
    });

    // Billing
    Route::prefix('billing')->group(function () {
        Route::get('/subscription', [BillingController::class, 'subscription']);
        Route::get('/usage', [BillingController::class, 'usage']);
        Route::post('/checkout', [BillingController::class, 'checkout']);
        Route::post('/portal', [BillingController::class, 'portal']);
        Route::post('/cancel', [BillingController::class, 'cancel']);
    });

    // Admin routes
     Route::middleware('permission:manage settings')->prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users/{userId}/deactivate', [AdminController::class, 'deactivateUser']);
        Route::post('/users/{userId}/activate', [AdminController::class, 'activateUser']);
        Route::delete('/users/{userId}', [AdminController::class, 'deleteUser']);
    });

    // Tasks
    Route::apiResource('tasks', TaskController::class)->only(['store'])
    ->middleware(['feature.limit:max_tasks','permission:create tasks']);

    Route::apiResource('tasks', TaskController::class)->except(['store']);

    Route::prefix('tasks')->group(function () {
        Route::post('/positions', [TaskController::class, 'updatePositions']);
        Route::get('/my-tasks', [TaskController::class, 'myTasks']);
    });

    // RBAC
    Route::middleware('permission:manage settings')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::post('/roles/assign', [RoleController::class, 'assignRole']);
        Route::get('/users/{userId}/permissions', [RoleController::class, 'userPermissions']);
    });

    
    // Analytics
    // Route::middleware('permission:view analytics')->group(function () {
    //     Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    //     Route::post('/analytics/track', [AnalyticsController::class, 'track']);
    // });

    // Analytics
    Route::middleware('permission:view analytics')->group(function () {
        Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
        Route::get('/analytics/task-trends', [AnalyticsController::class, 'taskTrends']);
        Route::get('/analytics/board-activity', [AnalyticsController::class, 'boardActivity']);
        Route::get('/analytics/user-activity', [AnalyticsController::class, 'userActivity']);
        Route::get('/analytics/priority-distribution', [AnalyticsController::class, 'priorityDistribution']);
        Route::get('/analytics/status-distribution', [AnalyticsController::class, 'statusDistribution']);
        Route::get('/analytics/export', [AnalyticsController::class, 'export']);
        Route::post('/analytics/track', [AnalyticsController::class, 'track']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });








        Route::get('/plans', function () {
        $plans = \App\Models\Plan::where('is_active', true)
            ->orderBy('price')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'price' => $plan->price,
                    'billing_period' => $plan->billing_period,
                    'trial_days' => $plan->trial_days,
                    'features' => $plan->features,
                    'is_active' => $plan->is_active,
                    'stripe_price_id' => $plan->stripe_price_id,
                ];
            });

        return \App\Http\Responses\ApiResponse::success(['plans' => $plans]);
    });
    
});
 

// });
