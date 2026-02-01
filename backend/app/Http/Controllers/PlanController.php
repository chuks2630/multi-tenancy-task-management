<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * List all active plans
     */
    public function index()
    {
        $plans = Plan::where('is_active', true)->get();
        return response()->json($plans);
    }

    /**
     * Show plan details
     */
    public function show(Plan $plan)
    {
        return response()->json($plan);
    }

    /**
     * Admin: Create plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:plans,slug',
            'stripe_price_id' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'billing_period' => 'required|in:monthly,yearly',
            'features' => 'required|array',
            'is_active' => 'boolean',
        ]);

        $plan = Plan::create($validated);

        return response()->json([
            'message' => 'Plan created successfully',
            'plan' => $plan,
        ], 201);
    }

    /**
     * Admin: Update plan
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'stripe_price_id' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'features' => 'sometimes|array',
            'is_active' => 'boolean',
        ]);

        $plan->update($validated);

        return response()->json([
            'message' => 'Plan updated successfully',
            'plan' => $plan->fresh(),
        ]);
    }

    /**
     * Admin: Delete plan
     */
    public function destroy(Plan $plan)
    {
        if ($plan->tenants()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete plan with active tenants',
            ], 422);
        }

        $plan->delete();

        return response()->json([
            'message' => 'Plan deleted successfully',
        ]);
    }
}