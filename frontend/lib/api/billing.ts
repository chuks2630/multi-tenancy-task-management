import apiClient from './client';
import { ApiResponse } from '@/lib/types';

export interface Plan {
  id: number;
  name: string;
  slug: string;
  description: string;
  price: number;
  billing_period: 'monthly' | 'yearly';
  trial_days: number;
  features: {
    max_teams: number;
    max_boards: number;
    max_tasks: number;
    max_users: number;
    analytics: boolean;
    priority_support: boolean;
    custom_branding: boolean;
  };
  is_active: boolean;
  stripe_price_id: string | null;
}

export interface Subscription {
  status: string;
  trial_ends_at: string | null;
  subscription_ends_at: string | null;
  is_on_trial: boolean;
  has_active_subscription: boolean;
  current_plan: Plan;
}

export interface UsageStats {
  teams: { current: number; limit: number };
  boards: { current: number; limit: number };
  tasks: { current: number; limit: number };
  users: { current: number; limit: number };
}

export const billingApi = {
  // Get all plans
  getPlans: async (): Promise<Plan[]> => {
    const response = await apiClient.get<ApiResponse<{ plans: Plan[] }>>('/plans');
    return response.data.data?.plans || [];
  },

  // Get current subscription
  getSubscription: async (): Promise<Subscription> => {
    const response = await apiClient.get<ApiResponse<Subscription>>('/billing/subscription');
    return response.data.data!;
  },

  // Get usage stats
  getUsage: async (): Promise<UsageStats> => {
    const response = await apiClient.get<ApiResponse<UsageStats>>('/billing/usage');
    return response.data.data!;
  },

  // Create checkout session
  createCheckoutSession: async (planId: number): Promise<{ url: string }> => {
    const response = await apiClient.post<ApiResponse<{ checkout_url: string }>>(
      '/billing/checkout',
      { plan_id: planId }
    );
    return { url: response.data.data!.checkout_url };
  },

  // Create portal session
  createPortalSession: async (): Promise<{ url: string }> => {
    const response = await apiClient.post<ApiResponse<{ portal_url: string }>>(
      '/billing/portal'
    );
    return { url: response.data.data!.portal_url };
  },

  // Change plan
  changePlan: async (planId: number): Promise<void> => {
    await apiClient.post('/billing/change-plan', { plan_id: planId });
  },

  // Cancel subscription
  cancelSubscription: async (): Promise<void> => {
    await apiClient.post('/billing/cancel');
  },
};