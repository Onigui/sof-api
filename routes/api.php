<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PropostaController;
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
        Route::get('propostas', [PropostaController::class, 'index']);
        Route::post('propostas', [PropostaController::class, 'store']);
        Route::get('propostas/{proposta}', [PropostaController::class, 'show']);
        Route::patch('propostas/{proposta}', [PropostaController::class, 'update']);
        Route::post('propostas/{proposta}/enviar', [PropostaController::class, 'enviar']);
    });
});
