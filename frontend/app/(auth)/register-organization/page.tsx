'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, CheckCircle2, XCircle, Building2 } from 'lucide-react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';

import { organizationRegistrationSchema, OrganizationRegistrationFormData } from '@/lib/validations/organization';
import { organizationApi } from '@/lib/api/organization';

export default function RegisterOrganizationPage() {
  const router = useRouter();
  const [error, setError] = useState<string>('');
  const [isLoading, setIsLoading] = useState(false);
  const [isCheckingSubdomain, setIsCheckingSubdomain] = useState(false);
  const [subdomainAvailable, setSubdomainAvailable] = useState<boolean | null>(null);

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors },
  } = useForm<OrganizationRegistrationFormData>({
    resolver: zodResolver(organizationRegistrationSchema),
  });

  const organizationName = watch('name');
  const subdomain = watch('subdomain');

  // Auto-generate subdomain from organization name
  useEffect(() => {
    if (organizationName && !subdomain) {
      const generated = organizationName
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 30);
      
      if (generated) {
        setValue('subdomain', generated);
      }
    }
  }, [organizationName, subdomain, setValue]);

  // Check subdomain availability with debounce
  useEffect(() => {
    if (!subdomain || subdomain.length < 3) {
      setSubdomainAvailable(null);
      return;
    }

    const timeoutId = setTimeout(async () => {
      setIsCheckingSubdomain(true);
      try {
        const result = await organizationApi.checkSubdomain(subdomain);
        setSubdomainAvailable(result.available);
      } catch (err) {
        setSubdomainAvailable(false);
      } finally {
        setIsCheckingSubdomain(false);
      }
    }, 500);

    return () => clearTimeout(timeoutId);
  }, [subdomain]);

  const onSubmit = async (data: OrganizationRegistrationFormData) => {
    setIsLoading(true);
    setError('');

    try {
      const response = await organizationApi.create(data);
      
      toast.success('Organization created successfully!');
      
      // Build tenant URL
      const tenantUrl = `http://${response.data.tenant.subdomain}.localhost:3000/login`;
      
      // Show success message
      toast.success('Redirecting to your organization...');
      
      // Redirect to tenant login
      setTimeout(() => {
        window.location.href = tenantUrl;
      }, 1000);
      
    } catch (err: any) {
      setError(err.response?.data?.message || err.message || 'Failed to create organization');
      toast.error('Organization creation failed');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 sm:px-6 lg:px-8">
      <Card className="w-full max-w-2xl">
        <CardHeader className="space-y-1">
          <div className="flex items-center justify-center mb-4">
            <div className="p-3 bg-primary/10 rounded-full">
              <Building2 className="h-6 w-6 text-primary" />
            </div>
          </div>
          <CardTitle className="text-2xl font-bold text-center">
            Create Your Organization
          </CardTitle>
          <CardDescription className="text-center">
            Start managing tasks with your team in minutes
          </CardDescription>
        </CardHeader>

        <form onSubmit={handleSubmit(onSubmit)}>
          <CardContent className="space-y-6">
            {error && (
              <Alert variant="destructive">
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}

            {/* Organization Details */}
            <div className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name">Organization Name</Label>
                <Input
                  id="name"
                  type="text"
                  placeholder="Acme Inc"
                  disabled={isLoading}
                  {...register('name')}
                />
                {errors.name && (
                  <p className="text-sm text-red-500">{errors.name.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="subdomain">Subdomain</Label>
                <div className="flex items-center gap-2">
                  <Input
                    id="subdomain"
                    type="text"
                    placeholder="acme"
                    disabled={isLoading}
                    {...register('subdomain')}
                  />
                  <span className="text-sm text-muted-foreground whitespace-nowrap">
                    .localhost:3000
                  </span>
                </div>
                
                {/* Subdomain status */}
                {subdomain && subdomain.length >= 3 && (
                  <div className="flex items-center gap-2 text-sm">
                    {isCheckingSubdomain ? (
                      <>
                        <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
                        <span className="text-muted-foreground">Checking availability...</span>
                      </>
                    ) : subdomainAvailable === true ? (
                      <>
                        <CheckCircle2 className="h-4 w-4 text-green-500" />
                        <span className="text-green-600">Available!</span>
                      </>
                    ) : subdomainAvailable === false ? (
                      <>
                        <XCircle className="h-4 w-4 text-red-500" />
                        <span className="text-red-600">Already taken</span>
                      </>
                    ) : null}
                  </div>
                )}
                
                {errors.subdomain && (
                  <p className="text-sm text-red-500">{errors.subdomain.message}</p>
                )}
                
                <p className="text-xs text-muted-foreground">
                  This will be your organization's URL: http://{subdomain || 'your-subdomain'}.localhost:3000
                </p>
              </div>
            </div>

            {/* Divider */}
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <span className="w-full border-t" />
              </div>
              <div className="relative flex justify-center text-xs uppercase">
                <span className="bg-background px-2 text-muted-foreground">
                  Owner Account
                </span>
              </div>
            </div>

            {/* Owner Details */}
            <div className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="owner_name">Your Full Name</Label>
                <Input
                  id="owner_name"
                  type="text"
                  placeholder="John Doe"
                  autoComplete="name"
                  disabled={isLoading}
                  {...register('owner_name')}
                />
                {errors.owner_name && (
                  <p className="text-sm text-red-500">{errors.owner_name.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="owner_email">Your Email</Label>
                <Input
                  id="owner_email"
                  type="email"
                  placeholder="john@acme.com"
                  autoComplete="email"
                  disabled={isLoading}
                  {...register('owner_email')}
                />
                {errors.owner_email && (
                  <p className="text-sm text-red-500">{errors.owner_email.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="owner_password">Password</Label>
                <Input
                  id="owner_password"
                  type="password"
                  placeholder="••••••••"
                  autoComplete="new-password"
                  disabled={isLoading}
                  {...register('owner_password')}
                />
                {errors.owner_password && (
                  <p className="text-sm text-red-500">{errors.owner_password.message}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="owner_password_confirmation">Confirm Password</Label>
                <Input
                  id="owner_password_confirmation"
                  type="password"
                  placeholder="••••••••"
                  autoComplete="new-password"
                  disabled={isLoading}
                  {...register('owner_password_confirmation')}
                />
                {errors.owner_password_confirmation && (
                  <p className="text-sm text-red-500">
                    {errors.owner_password_confirmation.message}
                  </p>
                )}
              </div>
            </div>

            <Button
              type="submit"
              className="w-full"
              disabled={isLoading || subdomainAvailable === false}
            >
              {isLoading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Creating organization...
                </>
              ) : (
                'Create Organization'
              )}
            </Button>
          </CardContent>

          <CardFooter className="flex flex-col space-y-4">
            <div className="text-sm text-center text-muted-foreground">
              Already have an account?{' '}
              <Link href="/login" className="text-primary hover:underline">
                Sign in
              </Link>
            </div>
          </CardFooter>
        </form>
      </Card>
    </div>
  );
}