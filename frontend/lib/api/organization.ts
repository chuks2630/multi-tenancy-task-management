import axios from 'axios';
import { OrganizationRegistrationData, TenantCreationResponse } from '@/lib/types';

// Create a separate axios instance for central API (without tenant detection)
const centralApiClient = axios.create({
  baseURL: process.env.NEXT_PUBLIC_CENTRAL_API_URL || 'http://localhost:8000/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

export const organizationApi = {
  // Create new organization
  create: async (data: OrganizationRegistrationData): Promise<TenantCreationResponse> => {
    const response = await centralApiClient.post<TenantCreationResponse>('/tenants', data);
    return response.data;
  },

  // Check subdomain availability
  checkSubdomain: async (subdomain: string): Promise<{ available: boolean }> => {
    const response = await centralApiClient.get<{ available: boolean }>(
      `/tenants/check-subdomain/${subdomain}`
    );
    return response.data;
  },
};