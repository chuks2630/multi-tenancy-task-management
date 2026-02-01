// User types
export interface User {
  id: number;
  name: string;
  email: string;
  role: 'owner' | 'admin' | 'member' | 'viewer';
  is_active: boolean;
  email_verified_at: string | null;
  created_at: string;
  permissions?: string[]; // Add this
  roles?: Array<{ name: string }>; // Add this
}

// Auth types
export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  data: {
    user: User;
    token: string;
    token_type: string;
  };
}

// Tenant types
export interface Tenant {
  id: string;
  name: string;
  subdomain: string;
}

// API Response types
export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data?: T;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
}

// Board types
export interface Board {
  id: number;
  name: string;
  description: string | null;
  color: string;
  is_private: boolean;
  is_active: boolean;
  tasks_count: number;
  creator: {
    id: number;
    name: string;
  };
  team: {
    id: number;
    name: string;
  } | null;
  created_at: string;
  updated_at: string;
}

// Task types
export type TaskStatus = 'todo' | 'in_progress' | 'done';
export type TaskPriority = 'low' | 'medium' | 'high' | 'urgent';

export interface Task {
  id: number;
  board_id: number;
  title: string;
  description: string | null;
  status: TaskStatus;
  priority: TaskPriority;
  position: number;
  assignee: {
    id: number;
    name: string;
    email: string;
  } | null;
  creator: {
    id: number;
    name: string;
  };
  due_date: string | null;
  is_overdue: boolean;
  created_at: string;
  updated_at: string;
}

// Team types
export interface Team {
  id: number;
  name: string;
  description: string | null;
  is_active: boolean;
  members_count: number;
  creator: {
    id: number;
    name: string;
    email: string;
  };
  created_at: string;
  updated_at: string;
}

export interface TeamMember {
  id: number;
  name: string;
  email: string;
  role: string;
  joined_at: string;
}

export interface OrganizationRegistrationData {
  name: string;
  subdomain: string;
  owner_name: string;
  owner_email: string;
  owner_password: string;
  owner_password_confirmation: string;
  plan_id?: number;
}

export interface TenantCreationResponse {
  success: boolean;
  message: string;
  data: {
    tenant: {
      id: string;
      name: string;
      subdomain: string;
    };
    owner: User;
    redirect_url: string;
  };
}

// Permission types
export type Permission =
  | 'view teams'
  | 'create teams'
  | 'edit teams'
  | 'delete teams'
  | 'manage teams'
  | 'view boards'
  | 'create boards'
  | 'edit boards'
  | 'delete boards'
  | 'manage boards'
  | 'view tasks'
  | 'create tasks'
  | 'edit tasks'
  | 'delete tasks'
  | 'assign tasks'
  | 'view users'
  | 'invite users'
  | 'edit users'
  | 'delete users'
  | 'view analytics'
  | 'manage settings';

export interface RolePermissions {
  owner: Permission[];
  admin: Permission[];
  member: Permission[];
  viewer: Permission[];
}
