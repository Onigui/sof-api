<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DocumentoController;
use App\Http\Controllers\Api\V1\FilaController;
use App\Http\Controllers\Api\V1\NotificacaoController;
use App\Http\Controllers\Api\V1\PendenciaController;
use App\Http\Controllers\Api\V1\PropostaController;
use App\Http\Controllers\Api\V1\RegiaoNormalizacaoController;
use App\Http\Controllers\Api\V1\RelatorioFechamentoController;
use App\Http\Controllers\Api\V1\RelatorioController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
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
        Route::get('propostas', [PropostaController::class, 'index']);
        Route::post('propostas', [PropostaController::class, 'store']);
        Route::get('propostas/{proposta}', [PropostaController::class, 'show']);
        Route::patch('propostas/{proposta}', [PropostaController::class, 'update']);
        Route::post('propostas/{proposta}/enviar', [PropostaController::class, 'enviar']);
        Route::get('propostas/{proposta}/documentos', [DocumentoController::class, 'index']);
        Route::post('propostas/{proposta}/documentos', [DocumentoController::class, 'store']);
        Route::get('propostas/{proposta}/pendencias', [PendenciaController::class, 'index']);
        Route::post('propostas/{proposta}/pendencias', [PendenciaController::class, 'store']);
        Route::patch('documentos/{documento}/validar', [DocumentoController::class, 'validar']);
        Route::patch('pendencias/{pendencia}/resolver', [PendenciaController::class, 'resolver']);
    });
});
