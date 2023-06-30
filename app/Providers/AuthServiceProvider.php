<?php

namespace App\Providers;

use App\Http\Enums\UserType;
use App\Policies\ServicePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Bypass permission check if user is super admin
        Gate::before(function($user, $ability){
           return $user->type === UserType::ADMIN->value ? true : null;
        });

        Gate::define('is-service-lead', function($user, $service){
            return $user->type === UserType::ADMIN->value || ($user->type === UserType::LEAD->value && $user->service->id === $service->id);
        });

        Gate::define('is-not-admin', function($user){
            return $user->type !== UserType::ADMIN->value;
        });
    }
}
