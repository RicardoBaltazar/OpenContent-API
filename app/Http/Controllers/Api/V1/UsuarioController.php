<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\ListarUsuariosRequest;
use App\Http\Requests\Usuario\StoreUsuarioRequest;
use App\Http\Requests\Usuario\UpdateUsuarioRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Usuários')]
class UsuarioController extends Controller
{
    public function __construct(private UserService $userService) {}

    /**
     * List users.
     */
    #[OA\Get(
        path: '/usuarios',
        tags: ['Usuários'],
        summary: 'Lista usuários com filtros e paginação',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'nome', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'email', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'papel', in: 'query', schema: new OA\Schema(type: 'string', enum: ['administrador', 'editor', 'autor', 'leitor'])),
            new OA\Parameter(name: 'ativo', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuários listados com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuarios'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Acesso negado.'),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
        ],
    )]
    public function index(ListarUsuariosRequest $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $usuarios = $this->userService->listar($request->validated());

        return UserResource::collection($usuarios)
            ->additional(['mensagem' => 'Usuários listados com sucesso.'])
            ->response();
    }

    /**
     * Create a user.
     */
    #[OA\Post(
        path: '/usuarios',
        tags: ['Usuários'],
        summary: 'Cria um usuário',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CriarUsuarioPayload'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuário criado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuario'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Acesso negado.'),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
        ],
    )]
    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $usuario = $this->userService->criar($request->validated());

        return (new UserResource($usuario))
            ->additional(['mensagem' => 'Usuário criado com sucesso.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a user.
     */
    #[OA\Get(
        path: '/usuarios/{usuario}',
        tags: ['Usuários'],
        summary: 'Retorna um usuário',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'usuario', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuário obtido com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuario'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Acesso negado.'),
            new OA\Response(response: 404, description: 'Usuário não encontrado.'),
        ],
    )]
    public function show(User $usuario): JsonResponse
    {
        $this->authorize('view', $usuario);

        return (new UserResource($usuario))
            ->additional(['mensagem' => 'Usuário obtido com sucesso.'])
            ->response();
    }

    /**
     * Update a user.
     */
    #[OA\Put(
        path: '/usuarios/{usuario}',
        tags: ['Usuários'],
        summary: 'Atualiza um usuário',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'usuario', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AtualizarUsuarioPayload'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuário atualizado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuario'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Acesso negado.'),
            new OA\Response(response: 404, description: 'Usuário não encontrado.'),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
        ],
    )]
    #[OA\Patch(
        path: '/usuarios/{usuario}',
        tags: ['Usuários'],
        summary: 'Atualiza parcialmente um usuário',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'usuario', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AtualizarUsuarioPayload'),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuário atualizado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuario'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Acesso negado.'),
            new OA\Response(response: 404, description: 'Usuário não encontrado.'),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
        ],
    )]
    public function update(UpdateUsuarioRequest $request, User $usuario): JsonResponse
    {
        $this->authorize('update', $usuario);

        $usuario = $this->userService->atualizar($usuario, $request->validated());

        return (new UserResource($usuario))
            ->additional(['mensagem' => 'Usuário atualizado com sucesso.'])
            ->response();
    }

    /**
     * Activate a user.
     */
    #[OA\Patch(
        path: '/usuarios/{usuario}/ativar',
        tags: ['Usuários'],
        summary: 'Ativa um usuário',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'usuario', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuário ativado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuario'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Acesso negado.'),
            new OA\Response(response: 404, description: 'Usuário não encontrado.'),
        ],
    )]
    public function ativar(User $usuario): JsonResponse
    {
        $this->authorize('toggleActive', $usuario);

        $usuario = $this->userService->ativar($usuario);

        return (new UserResource($usuario))
            ->additional(['mensagem' => 'Usuário ativado com sucesso.'])
            ->response();
    }

    /**
     * Deactivate a user.
     */
    #[OA\Patch(
        path: '/usuarios/{usuario}/desativar',
        tags: ['Usuários'],
        summary: 'Desativa um usuário e revoga seus tokens',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'usuario', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuário desativado com sucesso.',
                content: new OA\JsonContent(ref: '#/components/schemas/RespostaUsuario'),
            ),
            new OA\Response(response: 401, description: 'Não autenticado.'),
            new OA\Response(response: 403, description: 'Acesso negado.'),
            new OA\Response(response: 404, description: 'Usuário não encontrado.'),
            new OA\Response(
                response: 422,
                description: 'Não é possível desativar o último administrador ativo.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErroValidacao'),
            ),
        ],
    )]
    public function desativar(User $usuario): JsonResponse
    {
        $this->authorize('toggleActive', $usuario);

        $usuario = $this->userService->desativar($usuario);

        return (new UserResource($usuario))
            ->additional(['mensagem' => 'Usuário desativado com sucesso.'])
            ->response();
    }
}
