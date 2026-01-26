<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuditoriaController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\BillingCycleController;
use App\Http\Controllers\Api\V1\BillingInvoiceController;
use App\Http\Controllers\Api\V1\BillingWebhookController;
use App\Http\Controllers\Api\V1\DetranQueryController;
use App\Http\Controllers\Api\V1\DocumentoController;
use App\Http\Controllers\Api\V1\FilaController;
use App\Http\Controllers\Api\V1\IntegracaoController;
use App\Http\Controllers\Api\V1\NotificacaoController;
use App\Http\Controllers\Api\V1\PendenciaController;
use App\Http\Controllers\Api\V1\PropostaController;
use App\Http\Controllers\Api\V1\RegiaoNormalizacaoController;
use App\Http\Controllers\Api\V1\RelatorioFechamentoController;
use App\Http\Controllers\Api\V1\RelatorioController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureSubscriptionActive;

Route::prefix('v1')->group(function () {
    Route::post('billing/webhooks/{provider}', [BillingWebhookController::class, 'handle'])
        ->middleware('throttle:20,1');

    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('billing/cycle/run', [BillingCycleController::class, 'run']);
        Route::post('billing/cycle/dunning', [BillingCycleController::class, 'dunning']);
        Route::get('billing/invoices', [BillingInvoiceController::class, 'show']);
        Route::post('billing/invoices/{invoice}/checkout', [BillingInvoiceController::class, 'checkout']);
        Route::post('billing/invoices/{invoice}/mark-paid', [BillingInvoiceController::class, 'markPaid']);
        Route::get('billing/events', [BillingController::class, 'events']);
        Route::patch('billing/settings', [BillingController::class, 'settings']);
        Route::get('billing/summary', [BillingController::class, 'summary']);
        Route::get('detran/queries', [DetranQueryController::class, 'index']);
        Route::post('detran/queries', [DetranQueryController::class, 'store']);
        Route::post('detran/queries/{detranQuery}/complete-manual', [DetranQueryController::class, 'completeManual']);
        Route::get('auditoria', [AuditoriaController::class, 'index']);
        Route::get('auditoria/export', [AuditoriaController::class, 'export']);
        Route::get('fila', [FilaController::class, 'index']);
        Route::get('notificacoes', [NotificacaoController::class, 'index']);
        Route::get('regioes/pending-normalization', [RegiaoNormalizacaoController::class, 'pendingNormalization']);
        Route::post('regioes/normalize', [RegiaoNormalizacaoController::class, 'normalize']);
        Route::get('relatorios/aprovadas', [RelatorioController::class, 'aprovadas']);
        Route::get('relatorios/aprovadas/export', [RelatorioController::class, 'aprovadasExport']);
        Route::get('relatorios/fechamento', [RelatorioFechamentoController::class, 'index']);
        Route::get('relatorios/fechamento/{relatorioRun}/download', [RelatorioFechamentoController::class, 'download']);
        Route::post('relatorios/fechamento/{data_ref}/reenviar', [RelatorioFechamentoController::class, 'reenviar']);
        Route::get('relatorios/integradas', [RelatorioController::class, 'integradas']);
        Route::get('relatorios/integradas/export', [RelatorioController::class, 'integradasExport']);
        Route::post('notificacoes/{notification}/ler', [NotificacaoController::class, 'marcarLida']);
        Route::get('subscription', [SubscriptionController::class, 'show']);
        Route::patch('subscription', [SubscriptionController::class, 'update']);
        Route::get('propostas', [PropostaController::class, 'index']);
        Route::post('propostas', [PropostaController::class, 'store']);
        Route::get('propostas/{proposta}', [PropostaController::class, 'show']);
        Route::get('propostas/{proposta}/precheck', [PropostaController::class, 'precheck']);
        Route::patch('propostas/{proposta}', [PropostaController::class, 'update']);
        Route::post('propostas/{proposta}/enviar', [PropostaController::class, 'enviar']);
        Route::post('propostas/{proposta}/transferir', [PropostaController::class, 'transferir']);
        Route::post('propostas/{proposta}/ajustar-status', [PropostaController::class, 'ajustarStatus']);
        Route::post('propostas/{proposta}/integrar', [IntegracaoController::class, 'integrar'])
            ->middleware(EnsureSubscriptionActive::class);
        Route::get('propostas/{proposta}/documentos', [DocumentoController::class, 'index']);
        Route::post('propostas/{proposta}/documentos', [DocumentoController::class, 'store']);
        Route::post('propostas/{proposta}/documentos/auto-validate', [DocumentoController::class, 'autoValidate']);
        Route::get('propostas/{proposta}/pendencias', [PendenciaController::class, 'index']);
        Route::post('propostas/{proposta}/pendencias', [PendenciaController::class, 'store']);
        Route::patch('documentos/{documento}/validar', [DocumentoController::class, 'validar']);
        Route::patch('pendencias/{pendencia}/resolver', [PendenciaController::class, 'resolver']);
        Route::post('pendencias/{pendencia}/reabrir', [PendenciaController::class, 'reabrir']);
    });
});
