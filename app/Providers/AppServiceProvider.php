<?php

namespace App\Providers;

use App\Models\Pendencia;
use App\Models\Proposta;
use App\Policies\PendenciaPolicy;
use App\Models\Proposta;
use App\Policies\PropostaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Pendencia::class, PendenciaPolicy::class);
        Gate::policy(Proposta::class, PropostaPolicy::class);
    }
}
