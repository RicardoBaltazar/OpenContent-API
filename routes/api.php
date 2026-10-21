<?php

use App\Http\Controllers\Api\V1\AutenticacaoController;
use App\Http\Controllers\Api\V1\PerfilController;
use App\Http\Controllers\Api\V1\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('autenticacao')->group(function () {
        Route::post('cadastro', [AutenticacaoController::class, 'cadastro'])
            ->middleware('throttle:10,1');
        Route::post('entrar', [AutenticacaoController::class, 'entrar'])
            ->middleware('throttle:6,1');
        Route::post('esqueci-senha', [AutenticacaoController::class, 'esqueciSenha'])
            ->middleware('throttle:6,1');
        Route::get('redefinir-senha', [AutenticacaoController::class, 'validarRedefinicao'])
            ->name('password.reset');
        Route::post('redefinir-senha', [AutenticacaoController::class, 'redefinirSenha']);

        Route::middleware(['auth:sanctum', 'usuario.ativo'])->group(function () {
            Route::post('sair', [AutenticacaoController::class, 'sair']);
        });
    });

    Route::middleware(['auth:sanctum', 'usuario.ativo'])->group(function () {
        Route::get('perfil', [PerfilController::class, 'mostrar']);
        Route::match(['put', 'patch'], 'perfil', [PerfilController::class, 'atualizar']);
        Route::put('perfil/senha', [PerfilController::class, 'alterarSenha']);

        Route::prefix('usuarios')->group(function () {
            Route::get('/', [UsuarioController::class, 'index']);
            Route::post('/', [UsuarioController::class, 'store']);
            Route::get('{usuario}', [UsuarioController::class, 'show']);
            Route::match(['put', 'patch'], '{usuario}', [UsuarioController::class, 'update']);
            Route::patch('{usuario}/ativar', [UsuarioController::class, 'ativar']);
            Route::patch('{usuario}/desativar', [UsuarioController::class, 'desativar']);
        });
    });
});
