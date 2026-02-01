<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Organization - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <!-- Header -->
                <div class="text-center mb-4">
                    <h1 class="display-5 fw-bold">Create Your Organization</h1>
                    <p class="text-muted">Get started with your team workspace in minutes</p>
                </div>

                <!-- Errors -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">Please fix the following errors:</h6>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Form -->
                <div class="card shadow">
                    <div class="card-body p-4">
                        <form action="{{ route('tenant.store') }}" method="POST">
                            @csrf

                            <!-- Organization Details -->
                            <h5 class="mb-3">Organization Details</h5>
                            
                            <div class="mb-3">
                                <label for="organization_name" class="form-label">
                                    Organization Name <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control @error('organization_name') is-invalid @enderror" 
                                    id="organization_name" 
                                    name="organization_name" 
                                    value="{{ old('organization_name') }}"
                                    required
                                    placeholder="Acme Corporation"
                                >
                                @error('organization_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="subdomain" class="form-label">Subdomain (Optional)</label>
                                <div class="input-group">
                                    <input 
                                        type="text" 
                                        class="form-control @error('subdomain') is-invalid @enderror" 
                                        id="subdomain" 
                                        name="subdomain" 
                                        value="{{ old('subdomain') }}"
                                        placeholder="acme"
                                        pattern="[a-z0-9-]+"
                                    >
                                    <span class="input-group-text">.{{ config('app.domain') }}</span>
                                    @error('subdomain')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Leave blank to auto-generate from organization name</small>
                            </div>

                            <hr>

                            <!-- Owner Account -->
                            <h5 class="mb-3">Owner Account</h5>

                            <div class="mb-3">
                                <label for="owner_name" class="form-label">
                                    Full Name <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control @error('owner_name') is-invalid @enderror" 
                                    id="owner_name" 
                                    name="owner_name" 
                                    value="{{ old('owner_name') }}"
                                    required
                                >
                                @error('owner_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="owner_email" class="form-label">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="email" 
                                    class="form-control @error('owner_email') is-invalid @enderror" 
                                    id="owner_email" 
                                    name="owner_email" 
                                    value="{{ old('owner_email') }}"
                                    required
                                >
                                @error('owner_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="owner_password" class="form-label">
                                        Password <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        type="password" 
                                        class="form-control @error('owner_password') is-invalid @enderror" 
                                        id="owner_password" 
                                        name="owner_password" 
                                        required
                                    >
                                    @error('owner_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="owner_password_confirmation" class="form-label">
                                        Confirm Password <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="owner_password_confirmation" 
                                        name="owner_password_confirmation" 
                                        required
                                    >
                                </div>
                            </div>

                            <hr>

                            <!-- Plans -->
                            <h5 class="mb-3">Choose Your Plan</h5>

                            <div class="row">
                                @foreach ($plans as $plan)
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <input 
                                                    type="radio" 
                                                    class="btn-check" 
                                                    name="plan_id" 
                                                    id="plan_{{ $plan->id }}" 
                                                    value="{{ $plan->id }}"
                                                    @if($loop->first) checked @endif
                                                >
                                                <label class=" btn w-100" for="plan_{{ $plan->id }}">
                                                    <h5 class="card-title">{{ $plan->name }}</h5>
                                                    <h3 class="mb-3">
                                                        @if($plan->price > 0)
                                                            ${{ number_format($plan->price, 0) }}
                                                            <small class="text-muted">/{{ $plan->billing_period }}</small>
                                                        @else
                                                            Free
                                                        @endif
                                                    </h3>
                                                    <ul class="list-unstyled">
                                                        <li>✓ {{ $plan->features['max_users'] ?? 'Unlimited' }} users</li>
                                                        <li>✓ {{ $plan->features['max_boards'] ?? 'Unlimited' }} boards</li>
                                                    </ul>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Submit -->
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Create Organization
                                </button>
                            </div>

                            <p class="text-center text-muted small mt-3">
                                By creating an organization, you agree to our Terms of Service
                            </p>
                        </form>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-4">
                    <p class="text-muted">
                        Already have an account? 
                        <a href="{{ url('/login') }}">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-generate subdomain from organization name
        document.addEventListener('DOMContentLoaded', function() {
            const orgName = document.getElementById('organization_name');
            const subdomain = document.getElementById('subdomain');
            let manualEdit = false;

            subdomain.addEventListener('input', () => manualEdit = subdomain.value.length > 0);

            orgName.addEventListener('input', function() {
                if (!manualEdit) {
                    subdomain.value = this.value
                        .toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-|-$/g, '');
                }
            });
        });
    </script>
</body>
</html>