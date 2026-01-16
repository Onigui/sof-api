<?php

namespace App\Providers;

use App\Models\Documento;
use App\Models\Pendencia;
use App\Models\Proposta;
use App\Policies\DocumentoPolicy;
use App\Policies\PendenciaPolicy;
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
        Gate::policy(Documento::class, DocumentoPolicy::class);
        Gate::policy(Pendencia::class, PendenciaPolicy::class);
        Gate::policy(Proposta::class, PropostaPolicy::class);
    }
}
