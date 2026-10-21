<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class UserService
{
    /**
     * List users with optional filters.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        $query = User::query()->orderBy('id');

        if (! empty($filtros['nome'])) {
            $query->whereLike('name', '%'.$filtros['nome'].'%', caseSensitive: false);
        }

        if (! empty($filtros['email'])) {
            $query->whereLike('email', '%'.$filtros['email'].'%', caseSensitive: false);
        }

        if (! empty($filtros['papel'])) {
            $query->where('role', $filtros['papel']);
        }

        if (array_key_exists('ativo', $filtros)) {
            $query->where('is_active', filter_var($filtros['ativo'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate($filtros['per_page'] ?? 15);
    }

    /**
     * Create a user.
     *
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados): User
    {
        $usuario = new User;
        $usuario->name = $dados['nome'];
        $usuario->email = strtolower($dados['email']);
        $usuario->password = $dados['senha'];
        $usuario->role = UserRole::from($dados['papel']);
        $usuario->is_active = true;
        $usuario->save();

        return $usuario;
    }

    /**
     * Update a user.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(User $usuario, array $dados): User
    {
        $novoPapel = UserRole::from($dados['papel']);

        if (auth()->id() === $usuario->id && $novoPapel !== $usuario->role) {
            abort(403, 'Você não pode alterar o próprio papel.');
        }

        if (
            $usuario->role === UserRole::Administrador
            && $usuario->is_active
            && $novoPapel !== UserRole::Administrador
            && User::where('role', UserRole::Administrador->value)->where('is_active', true)->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'papel' => ['Não é possível rebaixar o último administrador ativo.'],
            ]);
        }

        $usuario->name = $dados['nome'];
        $usuario->email = strtolower($dados['email']);
        $usuario->role = $novoPapel;
        $usuario->save();

        return $usuario;
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizarPerfil(User $usuario, array $dados): User
    {
        $usuario->name = $dados['nome'];
        $usuario->email = strtolower($dados['email']);
        $usuario->save();

        return $usuario;
    }

    /**
     * Change the authenticated user's password and revoke other tokens.
     *
     * @param  array<string, mixed>  $dados
     */
    public function alterarSenha(User $usuario, array $dados): void
    {
        $tokenAtual = $usuario->currentAccessToken();

        $usuario->password = $dados['senha'];
        $usuario->save();

        $tokens = $usuario->tokens();

        if ($tokenAtual instanceof PersonalAccessToken) {
            $tokens->whereKeyNot($tokenAtual->getKey());
        }

        $tokens->delete();
    }

    /**
     * Activate a user.
     */
    public function ativar(User $usuario): User
    {
        if (! $usuario->is_active) {
            $usuario->is_active = true;
            $usuario->save();
        }

        return $usuario;
    }

    /**
     * Deactivate a user and revoke all access tokens.
     */
    public function desativar(User $usuario): User
    {
        if (auth()->id() === $usuario->id) {
            abort(403, 'Você não pode desativar a si mesmo.');
        }

        if (
            $usuario->role === UserRole::Administrador
            && $usuario->is_active
            && User::where('role', UserRole::Administrador->value)->where('is_active', true)->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'ativo' => ['Não é possível desativar o último administrador ativo.'],
            ]);
        }

        $usuario->is_active = false;
        $usuario->save();

        $usuario->tokens()->delete();

        return $usuario;
    }
}
