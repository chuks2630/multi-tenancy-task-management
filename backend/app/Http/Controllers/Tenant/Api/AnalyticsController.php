<?php

namespace App\Http\Controllers\Tenant\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Get analytics overview
     */
    public function overview(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $overview = $this->analyticsService->getOverview($startDate, $endDate);

        return ApiResponse::success($overview);
    }

    /**
     * Get task trends
     */
    public function taskTrends(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $interval = $request->input('interval', 'daily');

        $trends = $this->analyticsService->getTaskTrends($startDate, $endDate, $interval);

        return ApiResponse::success(['trends' => $trends]);
    }

    /**
     * Get board activity
     */
    public function boardActivity(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $boards = $this->analyticsService->getBoardActivity($startDate, $endDate);

        return ApiResponse::success(['boards' => $boards]);
    }

    /**
     * Get user activity
     */
    public function userActivity(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $users = $this->analyticsService->getUserActivity($startDate, $endDate);

        return ApiResponse::success(['users' => $users]);
    }

    /**
     * Get priority distribution
     */
    public function priorityDistribution(): JsonResponse
    {
        $distribution = $this->analyticsService->getPriorityDistribution();

        return ApiResponse::success(['distribution' => $distribution]);
    }

    /**
     * Get status distribution
     */
    public function statusDistribution(): JsonResponse
    {
        $distribution = $this->analyticsService->getStatusDistribution();

        return ApiResponse::success(['distribution' => $distribution]);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'csv');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return $this->analyticsService->exportData($startDate, $endDate, $format);
    }

    /**
     * Get dashboard analytics
     */
    public function dashboard(Request $request): JsonResponse
    {
        $period = $request->input('period', '30days');

        $analytics = $this->analyticsService->getDashboardAnalytics($period);

        return ApiResponse::success($analytics);
    }

    /**
     * Track custom event
     */
    public function track(Request $request): JsonResponse
    {
        $request->validate([
            'event_type' => 'required|string',
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ]);

        $this->analyticsService->track(
            $request->event_type,
            $request->user()->id,
            $request->entity_type,
            $request->entity_id,
            $request->metadata
        );

        return ApiResponse::success(null, 'Event tracked successfully');
    }
}