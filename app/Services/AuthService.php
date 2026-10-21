<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Register a new reader user and issue an access token.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    public function cadastrar(array $dados): array
    {
        $usuario = new User;
        $usuario->name = $dados['nome'];
        $usuario->email = strtolower($dados['email']);
        $usuario->password = $dados['senha'];
        $usuario->role = UserRole::Leitor;
        $usuario->is_active = true;
        $usuario->save();

        return $this->respostaComToken($usuario);
    }

    /**
     * Authenticate a user and issue a new access token.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    public function entrar(array $dados): array
    {
        $usuario = User::where('email', strtolower($dados['email']))->first();

        if (! $usuario || ! Hash::check($dados['senha'], $usuario->password)) {
            abort(401, 'Credenciais inválidas.');
        }

        if (! $usuario->is_active) {
            abort(403, 'Usuário inativo.');
        }

        return $this->respostaComToken($usuario);
    }

    /**
     * Revoke the token used in the current request.
     */
    public function sair(User $usuario): void
    {
        $token = $usuario->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Send the password reset link when the email belongs to a user.
     */
    public function solicitarRedefinicao(string $email): void
    {
        Password::sendResetLink(['email' => strtolower($email)]);
    }

    /**
     * Validate a password reset token.
     */
    public function validarTokenRedefinicao(string $email, string $token): void
    {
        $usuario = User::where('email', strtolower($email))->first();

        if (! $usuario || ! Password::getRepository()->exists($usuario, $token)) {
            throw ValidationException::withMessages([
                'token' => ['Token inválido ou expirado.'],
            ]);
        }
    }

    /**
     * Reset the user password and revoke all access tokens.
     *
     * @param  array<string, mixed>  $dados
     */
    public function redefinirSenha(array $dados): void
    {
        $status = Password::reset(
            [
                'email' => strtolower($dados['email']),
                'password' => $dados['senha'],
                'token' => $dados['token'],
            ],
            function (User $usuario, string $senha): void {
                $usuario->password = $senha;
                $usuario->save();

                $usuario->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [__($status)],
            ]);
        }
    }

    /**
     * Build the authenticated user response with a new token.
     *
     * @return array<string, mixed>
     */
    private function respostaComToken(User $usuario): array
    {
        return [
            'usuario' => $usuario,
            'token' => $usuario->createToken('api')->plainTextToken,
            'tipo_token' => 'Bearer',
        ];
    }
}
