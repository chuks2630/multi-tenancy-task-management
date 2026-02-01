import axios, { AxiosInstance, AxiosError } from 'axios';

// Get tenant subdomain from URL
const getTenantSubdomain = (): string | null => {
  if (typeof window === 'undefined') return null;
  
  const hostname = window.location.hostname;
  const parts = hostname.split('.');
  
  // localhost or single domain
  if (parts.length <= 1 || hostname === 'localhost') {
    return null;
  }
  
  // Get subdomain (first part)
  return parts[0];
};

// Get base URL based on tenant
const getBaseURL = (): string => {
  const subdomain = getTenantSubdomain();
  
  if (subdomain) {
    // Tenant API
    return `http://${subdomain}.localhost:8000/api`;
  }
  
  // Central API
  return process.env.NEXT_PUBLIC_CENTRAL_API_URL || 'http://localhost:8000/v1';
};

// Create axios instance
const apiClient: AxiosInstance = axios.create({
  baseURL: getBaseURL(),
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: false,
});

// Request interceptor - Add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Handle errors
apiClient.interceptors.response.use(
  (response) => {
    return response;
  },
  (error: AxiosError<any>) => {
    // Handle 401 Unauthorized
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      
      // Redirect to login
      if (typeof window !== 'undefined') {
        window.location.href = '/login';
      }
    }
    
    // Handle validation errors
    if (error.response?.status === 422) {
      return Promise.reject({
        message: error.response.data.message || 'Validation failed',
        errors: error.response.data.errors || {},
      });
    }
    
    // Handle other errors
    return Promise.reject({
      message: error.response?.data?.message || error.message || 'An error occurred',
      errors: error.response?.data?.errors || {},
    });
  }
);

export default apiClient;