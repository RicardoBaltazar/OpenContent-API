<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'OpenContent API',
    description: 'API de autenticação, perfil e gerenciamento de usuários.',
)]
#[OA\Server(url: '/api/v1', description: 'API v1')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
)]
#[OA\Tag(name: 'Autenticação', description: 'Cadastro, login, logout e recuperação de senha.')]
#[OA\Tag(name: 'Perfil', description: 'Dados e senha do usuário autenticado.')]
#[OA\Tag(name: 'Usuários', description: 'Gerenciamento administrativo de usuários.')]
#[OA\Schema(
    schema: 'Usuario',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'Maria Silva'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
        new OA\Property(property: 'papel', type: 'string', enum: ['administrador', 'editor', 'autor', 'leitor'], example: 'leitor'),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
        new OA\Property(property: 'criado_em', type: 'string', format: 'date-time', example: '2026-10-19T12:00:00-03:00'),
        new OA\Property(property: 'atualizado_em', type: 'string', format: 'date-time', example: '2026-10-19T12:00:00-03:00'),
    ],
)]
#[OA\Schema(
    schema: 'Erro',
    type: 'object',
    properties: [
        new OA\Property(property: 'mensagem', type: 'string', example: 'Usuário não encontrado.'),
    ],
)]
#[OA\Schema(
    schema: 'ErroValidacao',
    type: 'object',
    properties: [
        new OA\Property(property: 'mensagem', type: 'string', example: 'Dados inválidos.'),
        new OA\Property(property: 'errors', type: 'object', example: ['email' => ['O campo e-mail é obrigatório.']]),
    ],
)]
#[OA\Schema(
    schema: 'RespostaMensagem',
    type: 'object',
    properties: [
        new OA\Property(property: 'mensagem', type: 'string', example: 'Operação realizada com sucesso.'),
    ],
)]
#[OA\Schema(
    schema: 'RespostaUsuario',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/Usuario'),
        new OA\Property(property: 'mensagem', type: 'string', example: 'Usuário obtido com sucesso.'),
    ],
)]
#[OA\Schema(
    schema: 'RespostaUsuarios',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Usuario')),
        new OA\Property(property: 'links', type: 'object', example: ['first' => '/api/v1/usuarios?page=1', 'last' => '/api/v1/usuarios?page=1', 'prev' => null, 'next' => null]),
        new OA\Property(property: 'meta', type: 'object', example: ['current_page' => 1, 'from' => 1, 'last_page' => 1, 'path' => '/api/v1/usuarios', 'per_page' => 15, 'to' => 1, 'total' => 1]),
        new OA\Property(property: 'mensagem', type: 'string', example: 'Usuários listados com sucesso.'),
    ],
)]
#[OA\Schema(
    schema: 'RespostaAutenticacao',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'usuario', ref: '#/components/schemas/Usuario'),
                new OA\Property(property: 'token', type: 'string', example: '1|token-plano-texto'),
                new OA\Property(property: 'tipo_token', type: 'string', example: 'Bearer'),
            ],
        ),
        new OA\Property(property: 'mensagem', type: 'string', example: 'Login realizado com sucesso.'),
    ],
)]
#[OA\Schema(
    schema: 'RespostaRedefinicao',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
                new OA\Property(property: 'token', type: 'string', example: 'TOKEN'),
            ],
        ),
        new OA\Property(property: 'mensagem', type: 'string', example: 'Token válido. Envie a nova senha para concluir a redefinição.'),
    ],
)]
#[OA\Schema(
    schema: 'CadastroPayload',
    type: 'object',
    required: ['nome', 'email', 'senha', 'senha_confirmation'],
    properties: [
        new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Maria Silva'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'maria@exemplo.com'),
        new OA\Property(property: 'senha', type: 'string', minLength: 8, example: 'senha12345'),
        new OA\Property(property: 'senha_confirmation', type: 'string', minLength: 8, example: 'senha12345'),
    ],
)]
#[OA\Schema(
    schema: 'EntrarPayload',
    type: 'object',
    required: ['email', 'senha'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
        new OA\Property(property: 'senha', type: 'string', example: 'senha12345'),
    ],
)]
#[OA\Schema(
    schema: 'EsqueciSenhaPayload',
    type: 'object',
    required: ['email'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
    ],
)]
#[OA\Schema(
    schema: 'RedefinirSenhaPayload',
    type: 'object',
    required: ['email', 'token', 'senha', 'senha_confirmation'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'maria@exemplo.com'),
        new OA\Property(property: 'token', type: 'string', example: 'TOKEN'),
        new OA\Property(property: 'senha', type: 'string', minLength: 8, example: 'novaSenha123'),
        new OA\Property(property: 'senha_confirmation', type: 'string', minLength: 8, example: 'novaSenha123'),
    ],
)]
#[OA\Schema(
    schema: 'AtualizarPerfilPayload',
    type: 'object',
    required: ['nome', 'email'],
    properties: [
        new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Maria Souza'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'maria.souza@exemplo.com'),
    ],
)]
#[OA\Schema(
    schema: 'AlterarSenhaPayload',
    type: 'object',
    required: ['senha_atual', 'senha', 'senha_confirmation'],
    properties: [
        new OA\Property(property: 'senha_atual', type: 'string', example: 'senha12345'),
        new OA\Property(property: 'senha', type: 'string', minLength: 8, example: 'novaSenha123'),
        new OA\Property(property: 'senha_confirmation', type: 'string', minLength: 8, example: 'novaSenha123'),
    ],
)]
#[OA\Schema(
    schema: 'CriarUsuarioPayload',
    type: 'object',
    required: ['nome', 'email', 'senha', 'senha_confirmation', 'papel'],
    properties: [
        new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'João Pereira'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'joao@exemplo.com'),
        new OA\Property(property: 'senha', type: 'string', minLength: 8, example: 'senha12345'),
        new OA\Property(property: 'senha_confirmation', type: 'string', minLength: 8, example: 'senha12345'),
        new OA\Property(property: 'papel', type: 'string', enum: ['administrador', 'editor', 'autor', 'leitor'], example: 'autor'),
    ],
)]
#[OA\Schema(
    schema: 'AtualizarUsuarioPayload',
    type: 'object',
    required: ['nome', 'email', 'papel'],
    properties: [
        new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'João P. Pereira'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'joao.pereira@exemplo.com'),
        new OA\Property(property: 'papel', type: 'string', enum: ['administrador', 'editor', 'autor', 'leitor'], example: 'editor'),
    ],
)]
class OpenApi {}
