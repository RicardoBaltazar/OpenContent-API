<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\CadastroRequest;
use App\Http\Requests\Autenticacao\EntrarRequest;
use App\Http\Requests\Autenticacao\EsqueciSenhaRequest;
use App\Http\Requests\Autenticacao\RedefinirSenhaRequest;
use App\Http\Requests\Autenticacao\ValidarRedefinicaoRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Autenticação')]
class AutenticacaoController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Register a new reader user.
     */
    #[OA\Post(
        path: '/autenticacao/cadastro',
        tags: ['Autenticação'],
        summary: 'Cadastra um novo usuário leitor',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CadastroPayload'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Cadastro realizado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaAutenticacao'),
            ),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
            new OA\Response(response: 429, description: 'Muitas tentativas.'),
        ],
    )]
    public function cadastro(CadastroRequest $request): JsonResponse
    {
        $resultado = $this->authService->cadastrar($request->validated());

        return response()->json([
            'data' => [
                'usuario' => new UserResource($resultado['usuario']),
                'token' => $resultado['token'],
                'tipo_token' => $resultado['tipo_token'],
            ],
            'mensagem' => 'Cadastro realizado com sucesso.',
        ], 201);
    }

    /**
     * Authenticate a user.
     */
    #[OA\Post(
        path: '/autenticacao/entrar',
        tags: ['Autenticação'],
        summary: 'Realiza login e emite um token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/EntrarPayload'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login realizado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaAutenticacao'),
            ),
            new OA\Response(response: 401, description: 'Credenciais inválidas.'),
            new OA\Response(response: 403, description: 'Usuário inativo.'),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
            new OA\Response(response: 429, description: 'Muitas tentativas.'),
        ],
    )]
    public function entrar(EntrarRequest $request): JsonResponse
    {
        $resultado = $this->authService->entrar($request->validated());

        return response()->json([
            'data' => [
                'usuario' => new UserResource($resultado['usuario']),
                'token' => $resultado['token'],
                'tipo_token' => $resultado['tipo_token'],
            ],
            'mensagem' => 'Login realizado com sucesso.',
        ]);
    }

    /**
     * Request a password reset link.
     */
    #[OA\Post(
        path: '/autenticacao/esqueci-senha',
        tags: ['Autenticação'],
        summary: 'Solicita o link de redefinição de senha',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/EsqueciSenhaPayload'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Solicitação processada.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaMensagem'),
            ),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
            new OA\Response(response: 429, description: 'Muitas tentativas.'),
        ],
    )]
    public function esqueciSenha(EsqueciSenhaRequest $request): JsonResponse
    {
        $this->authService->solicitarRedefinicao($request->validated('email'));

        return response()->json([
            'mensagem' => 'Se o e-mail existir, enviaremos as instruções de redefinição.',
        ]);
    }

    /**
     * Validate a password reset token.
     */
    #[OA\Get(
        path: '/autenticacao/redefinir-senha',
        tags: ['Autenticação'],
        summary: 'Valida o token recebido no link de redefinição',
        parameters: [
            new OA\Parameter(name: 'token', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'email', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'email')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token válido.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaRedefinicao'),
            ),
            new OA\Response(
                response: 422,
                description: 'Token inválido ou expirado.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
        ],
    )]
    public function validarRedefinicao(ValidarRedefinicaoRequest $request): JsonResponse
    {
        $dados = $request->validated();

        $this->authService->validarTokenRedefinicao($dados['email'], $dados['token']);

        return response()->json([
            'data' => [
                'email' => $dados['email'],
                'token' => $dados['token'],
            ],
            'mensagem' => 'Token válido. Envie a nova senha para concluir a redefinição.',
        ]);
    }

    /**
     * Reset the user password.
     */
    #[OA\Post(
        path: '/autenticacao/redefinir-senha',
        tags: ['Autenticação'],
        summary: 'Redefine a senha com o token de recuperação',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RedefinirSenhaPayload'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Senha redefinida com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaMensagem'),
            ),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
        ],
    )]
    public function redefinirSenha(RedefinirSenhaRequest $request): JsonResponse
    {
        $this->authService->redefinirSenha($request->validated());

        return response()->json([
            'mensagem' => 'Senha redefinida com sucesso.',
        ]);
    }

    /**
     * Revoke the current access token.
     */
    #[OA\Post(
        path: '/autenticacao/sair',
        tags: ['Autenticação'],
        summary: 'Revoga o token usado na requisição atual',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout realizado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaMensagem'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Usuário inativo.'),
        ],
    )]
    public function sair(Request $request): JsonResponse
    {
        $this->authService->sair($request->user());

        return response()->json([
            'mensagem' => 'Logout realizado com sucesso.',
        ]);
    }
}
