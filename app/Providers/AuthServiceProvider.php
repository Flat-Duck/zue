<?php

namespace App\Providers;

use App\Models\ScopePolicy;
use App\Policies\ManagementScopePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // The model is `ScopePolicy`, which auto-discovery would look for as
        // `ScopePolicyPolicy`. The thing itself is a management scope.
        ScopePolicy::class => ManagementScopePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Automatically finding the Policies
        Gate::guessPolicyNamesUsing(function ($modelClass) {
            return 'App\\Policies\\'.class_basename($modelClass).'Policy';
        });

        $this->registerPolicies();

        Gate::define('maintenance', function ($user): bool {
            return $user->isSuperAdmin()
                || $user->permissions()->where('name', 'manage maintenance')->exists();
        });

        Gate::define('manage-operations', function ($user): bool {
            return $user->permissions()->where('name', 'manage operations')->exists();
        });

        Gate::define('manage-clinic', function ($user): bool {
            return $user->permissions()->where('name', 'manage clinic')->exists();
        });

        Gate::define('manage-appraisals', function ($user): bool {
            return $user->hasAnyRole(['hr', 'admin', 'super-admin']);
        });

        // Implicitly grant "Super Admin" role all permission checks using can()
        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });
    }
}
