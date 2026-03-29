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

// Tenant API health check
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Tenant API is active',
        'tenant' => [
            'id'   => tenant('id'),
            'name' => tenant('name'),
        ],
    ]);
});

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::get('/me',       [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/',          [DashboardController::class, 'index']);
        Route::get('/my-tasks',  [DashboardController::class, 'myTasks']);
    });

    // Teams
    // Apply feature + permission middleware only to the store action
    // Route::apiResource('teams', TeamController::class);
    Route::get('teams', [TeamController::class, 'index']);
    Route::get('teams/{team}', [TeamController::class, 'show']);
    Route::put('teams/{team}', [TeamController::class, 'update']);
    Route::delete('teams/{team}', [TeamController::class, 'destroy']);
    Route::middleware(['feature.limit:max_teams', 'permission:create teams'])
        ->group(function () {
            Route::post('teams', [TeamController::class, 'store'])->name('teams.store.limited');
        });

    Route::prefix('teams/{team}')->group(function () {
        Route::get('/members',          [TeamController::class, 'members']);
        Route::post('/members',         [TeamController::class, 'addMember']);
        Route::delete('/members',       [TeamController::class, 'removeMember']);
        Route::put('/members/role',     [TeamController::class, 'updateMemberRole']);
    });

    // Invitations
    Route::prefix('invitations')->group(function () {
        Route::get('/',                   [InvitationController::class, 'index']);
        Route::get('/pending',            [InvitationController::class, 'pending']);
        Route::post('/',                  [InvitationController::class, 'store']);
        Route::post('/{token}/accept',    [InvitationController::class, 'accept']);
        Route::post('/{token}/reject',    [InvitationController::class, 'reject']);
        Route::delete('/{invitation}',    [InvitationController::class, 'cancel']);
    });

    // Boards
    Route::apiResource('boards', BoardController::class);
    Route::middleware(['feature.limit:max_boards', 'permission:create boards'])
        ->group(function () {
            Route::post('boards', [BoardController::class, 'store'])->name('boards.store.limited');
        });

    Route::prefix('boards/{board}')->group(function () {
        Route::get('/statistics',     [BoardController::class, 'statistics']);
        Route::post('/members',       [BoardController::class, 'addMember']);
        Route::delete('/members',     [BoardController::class, 'removeMember']);
    });

    // Tasks
    Route::apiResource('tasks', TaskController::class);
    Route::middleware(['feature.limit:max_tasks', 'permission:create tasks'])
        ->group(function () {
            Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store.limited');
        });

    Route::prefix('tasks')->group(function () {
        Route::post('/positions',  [TaskController::class, 'updatePositions']);
        Route::get('/my-tasks',    [TaskController::class, 'myTasks']);
    });

    // Billing
    Route::prefix('billing')->group(function () {
        Route::get('/subscription',  [BillingController::class, 'subscription']);
        Route::get('/usage',         [BillingController::class, 'usage']);
        Route::post('/checkout',     [BillingController::class, 'checkout']);
        Route::post('/portal',       [BillingController::class, 'portal']);
        Route::post('/cancel',       [BillingController::class, 'cancel']);
    });

    // Admin
    Route::middleware('permission:manage settings')->prefix('admin')->group(function () {
        Route::get('/users',                          [AdminController::class, 'users']);
        Route::post('/users/{userId}/deactivate',     [AdminController::class, 'deactivateUser']);
        Route::post('/users/{userId}/activate',       [AdminController::class, 'activateUser']);
        Route::delete('/users/{userId}',              [AdminController::class, 'deleteUser']);
    });

    // RBAC
    Route::middleware('permission:manage settings')->group(function () {
        Route::get('/roles',                          [RoleController::class, 'index']);
        Route::get('/permissions',                    [RoleController::class, 'permissions']);
        Route::post('/roles/assign',                  [RoleController::class, 'assignRole']);
        Route::get('/users/{userId}/permissions',     [RoleController::class, 'userPermissions']);
    });

    // Analytics
    Route::middleware('permission:view analytics')->group(function () {
        Route::get('/analytics/overview',              [AnalyticsController::class, 'overview']);
        Route::get('/analytics/task-trends',           [AnalyticsController::class, 'taskTrends']);
        Route::get('/analytics/board-activity',        [AnalyticsController::class, 'boardActivity']);
        Route::get('/analytics/user-activity',         [AnalyticsController::class, 'userActivity']);
        Route::get('/analytics/priority-distribution', [AnalyticsController::class, 'priorityDistribution']);
        Route::get('/analytics/status-distribution',   [AnalyticsController::class, 'statusDistribution']);
        Route::get('/analytics/export',                [AnalyticsController::class, 'export']);
        Route::post('/analytics/track',                [AnalyticsController::class, 'track']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/',               [NotificationController::class, 'index']);
        Route::get('/unread',         [NotificationController::class, 'unread']);
        Route::post('/{id}/read',     [NotificationController::class, 'markAsRead']);
        Route::post('/read-all',      [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}',        [NotificationController::class, 'destroy']);
    });

    // Plans (tenant-scoped)
    Route::get('/plans', function () {
        $plans = \App\Models\Plan::where('is_active', true)
            ->orderBy('price')
            ->get()
            ->map(fn($plan) => [
                'id'              => $plan->id,
                'name'            => $plan->name,
                'slug'            => $plan->slug,
                'description'     => $plan->description,
                'price'           => $plan->price,
                'billing_period'  => $plan->billing_period,
                'trial_days'      => $plan->trial_days,
                'features'        => $plan->features,
                'is_active'       => $plan->is_active,
                'stripe_price_id' => $plan->stripe_price_id,
            ]);

        return \App\Http\Responses\ApiResponse::success(['plans' => $plans]);
    });
});