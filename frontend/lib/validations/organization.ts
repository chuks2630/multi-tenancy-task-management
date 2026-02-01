import { z } from 'zod';

export const organizationRegistrationSchema = z.object({
  name: z
    .string()
    .min(2, 'Organization name must be at least 2 characters')
    .max(255, 'Organization name cannot exceed 255 characters'),
  
  subdomain: z
    .string()
    .min(3, 'Subdomain must be at least 3 characters')
    .max(63, 'Subdomain cannot exceed 63 characters')
    .regex(/^[a-z0-9-]+$/, 'Subdomain can only contain lowercase letters, numbers, and hyphens')
    .regex(/^[a-z0-9]/, 'Subdomain must start with a letter or number')
    .regex(/[a-z0-9]$/, 'Subdomain must end with a letter or number')
    .refine((val) => {
      const reserved = ['www', 'app', 'api', 'admin', 'mail', 'ftp', 'localhost', 'dashboard'];
      return !reserved.includes(val);
    }, 'This subdomain is reserved'),
  
  owner_name: z
    .string()
    .min(2, 'Name must be at least 2 characters')
    .max(255, 'Name cannot exceed 255 characters'),
  
  owner_email: z
    .string()
    .min(1, 'Email is required')
    .email('Please enter a valid email address'),
  
  owner_password: z
    .string()
    .min(8, 'Password must be at least 8 characters')
    .regex(/[A-Z]/, 'Password must contain at least one uppercase letter')
    .regex(/[a-z]/, 'Password must contain at least one lowercase letter')
    .regex(/[0-9]/, 'Password must contain at least one number'),
  
  owner_password_confirmation: z
    .string()
    .min(1, 'Please confirm your password'),
  
  plan_id: z.number().optional(),
}).refine((data) => data.owner_password === data.owner_password_confirmation, {
  message: "Passwords don't match",
  path: ['owner_password_confirmation'],
});

export type OrganizationRegistrationFormData = z.infer<typeof organizationRegistrationSchema>;