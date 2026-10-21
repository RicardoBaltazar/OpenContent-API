<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Perfil\AlterarSenhaRequest;
use App\Http\Requests\Perfil\AtualizarPerfilRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Perfil')]
class PerfilController extends Controller
{
    public function __construct(private UserService $userService) {}

    /**
     * Show the authenticated user's profile.
     */
    #[OA\Get(
        path: '/perfil',
        tags: ['Perfil'],
        summary: 'Retorna o perfil do usuário autenticado',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil obtido com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuario'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Usuário inativo.'),
        ],
    )]
    public function mostrar(Request $request): JsonResponse
    {
        return (new UserResource($request->user()))
            ->additional(['mensagem' => 'Perfil obtido com sucesso.'])
            ->response();
    }

    /**
     * Update the authenticated user's profile.
     */
    #[OA\Put(
        path: '/perfil',
        tags: ['Perfil'],
        summary: 'Atualiza o perfil do usuário autenticado',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AtualizarPerfilPayload'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil atualizado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuario'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Usuário inativo.'),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
        ],
    )]
    #[OA\Patch(
        path: '/perfil',
        tags: ['Perfil'],
        summary: 'Atualiza parcialmente o perfil do usuário autenticado',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AtualizarPerfilPayload'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil atualizado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuario'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Usuário inativo.'),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
        ],
    )]
    public function atualizar(AtualizarPerfilRequest $request): JsonResponse
    {
        $usuario = $this->userService->atualizarPerfil($request->user(), $request->validated());

        return (new UserResource($usuario))
            ->additional(['mensagem' => 'Perfil atualizado com sucesso.'])
            ->response();
    }

    /**
     * Change the authenticated user's password.
     */
    #[OA\Put(
        path: '/perfil/senha',
        tags: ['Perfil'],
        summary: 'Altera a senha do usuário autenticado',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AlterarSenhaPayload'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Senha alterada com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaMensagem'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Usuário inativo.'),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
        ],
    )]
    public function alterarSenha(AlterarSenhaRequest $request): JsonResponse
    {
        $this->userService->alterarSenha($request->user(), $request->validated());

        return response()->json([
            'mensagem' => 'Senha alterada com sucesso.',
        ]);
    }
}
