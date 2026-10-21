8# Spec: Autenticação e Usuários

- **Task:** `auth-e-user`
- **Status:** implementado; migrations e seed pendentes de execução pelo desenvolvedor
- **Data:** 2026-10-19
- **Base:** Laravel 13.38, PHP 8.3-8.5, PostgreSQL, ambiente via Laravel Sail

## 1. Contexto

Projeto Laravel recém-criado, atualmente sem API, sem autenticação e sem documentação OpenAPI.
O `AGENTS.md` ainda descreve uma arquitetura .NET; nesta task ele será atualizado para as convenções
do Laravel, mantendo as regras de commits, LGPD e endpoints em pt-BR.

O objetivo desta task é entregar o fluxo completo de autenticação e gerenciamento de usuários via API,
incluindo níveis de acesso, recuperação de senha, perfil e ativação/desativação.

## 2. Decisões fechadas

| Tema | Decisão |
| --- | --- |
| Autenticação | Laravel Sanctum com tokens pessoais |
| Expiração do token | Sem expiração; logout revoga o token atual |
| Cadastro | Público (self-signup) + criação por administrador |
| Papel no self-signup | `leitor` |
| Papéis | Enum `UserRole` + Policies nativas |
| Status | Booleano `is_active` |
| Verificação de e-mail | Não exigida nesta versão |
| Recuperação de senha | Laravel Password Broker via e-mail |
| Link de redefinição | Aponta para a própria API |
| Documentação | L5-Swagger (OpenAPI 3 + Swagger UI) |
| Versionamento | `/api/v1` |
| Gestão de usuários | Somente administrador |
| Escopo admin | Listar, detalhar, criar, editar, ativar e desativar |
| Exclusão | Não haverá hard delete; desativação é o equivalente |
| Dados do usuário | Nome, e-mail, senha, papel e status |
| Alteração de e-mail | Livre pelo próprio usuário, sem verificação |
| Troca de senha | Exige senha atual e confirmação |
| Proteção do admin | Não pode rebaixar/desativar a si mesmo nem remover o último admin ativo |
| Formato de resposta | Envelope `data`/`mensagem`; erros com `mensagem`/`errors` |
| Idioma | Mensagens e campos JSON em pt-BR |
| Testes automatizados | Não serão criados nesta task |
| Arquitetura | Controller + Service + Model + Enum + Policy; sem Repository |
| Fuso horário | `America/Sao_Paulo` |
| E-mail de redefinição | Conteúdo em pt-BR via `lang/pt_BR.json` |
| Regra do último admin | `422` para conflito de papel/status; `403` para ações contra si mesmo |
| Normalização de e-mail | Em `prepareForValidation()` dos Form Requests |

## 3. Escopo

### 3.1 Dentro do escopo

- Instalação e configuração do Sanctum.
- Instalação e configuração do L5-Swagger.
- Cadastro público de usuário.
- Login, logout e proteção das rotas autenticadas.
- Recuperação e redefinição de senha por e-mail.
- Visualização e atualização do próprio perfil.
- Troca de senha do próprio usuário.
- CRUD administrativo de usuários.
- Ativação e desativação de usuários.
- Bloqueio de usuários inativos.
- Revogação de tokens em logout, desativação e troca de senha.
- Papéis e autorização com Policies.
- Validação de entrada com Form Requests.
- Respostas padronizadas e mensagens em pt-BR.
- Documentação OpenAPI de todos os endpoints.
- Atualização do `AGENTS.md` para as convenções Laravel.

### 3.2 Fora do escopo

- Verificação de e-mail.
- Frontend ou telas HTML.
- Refresh token ou OAuth2.
- Login social.
- Autenticação de dois fatores.
- Permissões de conteúdo para editor/autor/leitor (serão definidas em tasks futuras).
- Upload de avatar, telefone, CPF ou outros dados pessoais.
- Exclusão definitiva de usuários.
- Testes automatizados.
- Logs de auditoria.
- CORS e configuração de clientes externos.
- Alteração de senha de terceiros por administrador.

## 4. Papéis e permissões

### 4.1 Enum

`App\Enums\UserRole`

| Case | Valor persistido | Descrição |
| --- | --- | --- |
| `Administrador` | `administrador` | Gerencia usuários e possui acesso administrativo |
| `Editor` | `editor` | Papel reservado para regras de conteúdo futuras |
| `Autor` | `autor` | Papel reservado para regras de conteúdo futuras |
| `Leitor` | `leitor` | Papel padrão do cadastro público; sem acesso administrativo |

Nesta task, a única diferença efetiva de permissão é `administrador` versus os demais papéis.
As permissões de conteúdo de editor, autor e leitor ficam para tasks futuras.

### 4.2 Matriz de autorização

| Recurso | Administrador | Editor | Autor | Leitor | Inativo |
| --- | --- | --- | --- | --- | --- |
| Cadastro público | Público | Público | Público | Público | Público |
| Login | Sim | Sim | Sim | Sim | Não |
| Próprio perfil | Sim | Sim | Sim | Sim | Não |
| Listar usuários | Sim | Não | Não | Não | Não |
| Detalhar qualquer usuário | Sim | Não | Não | Não | Não |
| Criar usuário | Sim | Não | Não | Não | Não |
| Editar qualquer usuário | Sim | Não | Não | Não | Não |
| Ativar/desativar usuário | Sim | Não | Não | Não | Não |

### 4.3 UserPolicy

Arquivo: `app/Policies/UserPolicy.php`

Métodos:

- `viewAny(User $user): bool`
- `view(User $user, User $target): bool`
- `create(User $user): bool`
- `update(User $user, User $target): bool`
- `toggleActive(User $user, User $target): bool`

Todos retornam `true` somente quando `$user->role === UserRole::Administrador`.
A Policy será descoberta automaticamente pelo Laravel para o model `User`.

## 5. Modelo de dados

### 5.1 Tabela `users`

A tabela existente será alterada por uma nova migration:

```text
role        VARCHAR(20) NOT NULL DEFAULT 'leitor'
is_active   BOOLEAN NOT NULL DEFAULT TRUE
```

Índices:

```text
INDEX users_role_index (role)
INDEX users_is_active_index (is_active)
```

A migration não será executada pelo agente. O desenvolvedor deverá rodá-la manualmente.

### 5.2 Model `User`

Alterações:

- Adicionar `Laravel\Sanctum\HasApiTokens`.
- Adicionar cast `role` para `UserRole::class`.
- Adicionar cast `is_active` para `boolean`.
- Manter `name`, `email` e `password` como campos preenchíveis.
- `role` e `is_active` não entram em `Fillable`; serão atribuídos explicitamente nos Services.
- Manter `password` e `remember_token` ocultos.
- Não implementar `MustVerifyEmail`.

### 5.3 Campos da API

Os campos JSON serão em português. O mapeamento com o banco permanece:

| API | Banco |
| --- | --- |
| `nome` | `name` |
| `email` | `email` |
| `senha` | `password` |
| `senha_confirmation` | confirmação de `password` |
| `senha_atual` | validação da senha atual |
| `papel` | `role` |
| `ativo` | `is_active` |

### 5.4 Factory

`database/factories/UserFactory.php`:

- Estado padrão: `role = leitor`, `is_active = true`.
- Estados adicionais: `administrador()`, `editor()`, `autor()`, `leitor()`, `inativo()`.

### 5.5 Seeder do admin inicial

`database/seeders/DatabaseSeeder.php`:

- Lê `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD`.
- Cria o administrador somente se `ADMIN_EMAIL` e `ADMIN_PASSWORD` estiverem preenchidos.
- Cria somente quando o e-mail ainda não existir, para não sobrescrever a senha a cada execução.
- Define `role = administrador` e `is_active = true`.
- Não registra e-mail nem senha em logs.

Variáveis adicionadas ao `.env.example`:

```dotenv
ADMIN_NAME="Administrador"
ADMIN_EMAIL=
ADMIN_PASSWORD=
```

O seeder não será executado pelo agente.

## 6. Contrato da API

Base: `/api/v1`

### 6.1 Envelope de resposta

Sucesso com recurso:

```json
{
  "data": {},
  "mensagem": "Operação realizada com sucesso."
}
```

Sucesso com listagem paginada:

```json
{
  "data": [],
  "links": {},
  "meta": {},
  "mensagem": "Usuários listados com sucesso."
}
```

Erro:

```json
{
  "mensagem": "Dados inválidos.",
  "errors": {
    "email": ["O campo e-mail é obrigatório."]
  }
}
```

Erro sem validação:

```json
{
  "mensagem": "Usuário não encontrado."
}
```

### 6.2 Recurso `Usuario`

```json
{
  "id": 1,
  "nome": "Maria Silva",
  "email": "maria@exemplo.com",
  "papel": "leitor",
  "ativo": true,
  "criado_em": "2026-10-19T12:00:00-03:00",
  "atualizado_em": "2026-10-19T12:00:00-03:00"
}
```

Nunca retornar `password`, `remember_token`, `email_verified_at` ou tokens de terceiros.

### 6.3 Endpoints públicos

#### POST `/autenticacao/cadastro`

Request:

```json
{
  "nome": "Maria Silva",
  "email": "maria@exemplo.com",
  "senha": "senha12345",
  "senha_confirmation": "senha12345"
}
```

Regras:

- Cria usuário com `papel = leitor` e `ativo = true`.
- Normaliza o e-mail para minúsculas.
- Cria token Sanctum.
- Não permite informar `papel` ou `ativo`.

Resposta `201 Created`:

```json
{
  "data": {
    "usuario": {
      "id": 1,
      "nome": "Maria Silva",
      "email": "maria@exemplo.com",
      "papel": "leitor",
      "ativo": true,
      "criado_em": "2026-10-19T12:00:00-03:00",
      "atualizado_em": "2026-10-19T12:00:00-03:00"
    },
    "token": "1|token-plano-texto",
    "tipo_token": "Bearer"
  },
  "mensagem": "Cadastro realizado com sucesso."
}
```

Erros: `422` para validação, `429` para excesso de tentativas.

#### POST `/autenticacao/entrar`

Request:

```json
{
  "email": "maria@exemplo.com",
  "senha": "senha12345"
}
```

Regras:

- Normaliza o e-mail.
- Credenciais inválidas retornam `401` com mensagem genérica.
- Usuário inativo com credenciais válidas retorna `403`.
- Cria um novo token Sanctum a cada login.

Resposta `200 OK`:

```json
{
  "data": {
    "usuario": {},
    "token": "2|token-plano-texto",
    "tipo_token": "Bearer"
  },
  "mensagem": "Login realizado com sucesso."
}
```

Erros: `401`, `403`, `422`, `429`.

#### POST `/autenticacao/esqueci-senha`

Request:

```json
{
  "email": "maria@exemplo.com"
}
```

Regras:

- Usa o Password Broker do Laravel.
- Sempre retorna a mesma mensagem, exista ou não o e-mail, para evitar enumeração.
- Envia o link de redefinição por e-mail.
- Em desenvolvimento, `MAIL_MAILER=log` faz o link aparecer no log.

Resposta `200 OK`:

```json
{
  "mensagem": "Se o e-mail existir, enviaremos as instruções de redefinição."
}
```

Erros: `422`, `429`.

#### GET `/autenticacao/redefinir-senha`

Query string:

```text
?token=TOKEN&email=maria@exemplo.com
```

Essa rota é o destino do link enviado por e-mail e deve ser nomeada como `password.reset`,
pois a notificação nativa do Laravel depende desse nome de rota.

Regras:

- Valida se o token existe e não expirou.
- Retorna o e-mail e o token para que o cliente possa concluir a redefinição.
- Não altera a senha.

Resposta `200 OK`:

```json
{
  "data": {
    "email": "maria@exemplo.com",
    "token": "TOKEN"
  },
  "mensagem": "Token válido. Envie a nova senha para concluir a redefinição."
}
```

Erros: `422` para token inválido ou expirado.

#### POST `/autenticacao/redefinir-senha`

Request:

```json
{
  "email": "maria@exemplo.com",
  "token": "TOKEN",
  "senha": "novaSenha123",
  "senha_confirmation": "novaSenha123"
}
```

Regras:

- Usa `Password::reset`.
- Revoga todos os tokens do usuário após a redefinição.
- Remove o token de redefinição consumido.

Resposta `200 OK`:

```json
{
  "mensagem": "Senha redefinida com sucesso."
}
```

Erros: `422`.

### 6.4 Endpoints autenticados

Todos exigem `Authorization: Bearer {token}` e usuário ativo.

#### POST `/autenticacao/sair`

- Revoga somente o token usado na requisição atual.

Resposta `200 OK`:

```json
{
  "mensagem": "Logout realizado com sucesso."
}
```

#### GET `/perfil`

Resposta `200 OK` com o usuário autenticado.

#### PUT/PATCH `/perfil`

Request:

```json
{
  "nome": "Maria Souza",
  "email": "maria.souza@exemplo.com"
}
```

Regras:

- Ambos os campos são obrigatórios.
- O e-mail deve ser único, ignorando o próprio usuário.
- Não permite alterar `papel` ou `ativo`.

Resposta `200 OK` com o usuário atualizado.

#### PUT `/perfil/senha`

Request:

```json
{
  "senha_atual": "senha12345",
  "senha": "novaSenha123",
  "senha_confirmation": "novaSenha123"
}
```

Regras:

- Valida `senha_atual` com `current_password:sanctum` (o guard `web` não tem usuário em requisições por token).
- Revoga todos os outros tokens do usuário, mantendo o token atual.
- Não permite que a nova senha seja igual à atual (`different:senha_atual`).

Resposta `200 OK`:

```json
{
  "mensagem": "Senha alterada com sucesso."
}
```

### 6.5 Endpoints administrativos

Todos exigem autenticação, usuário ativo e papel `administrador`.

#### GET `/usuarios`

Query parameters opcionais:

| Parâmetro | Tipo | Regra |
| --- | --- | --- |
| `nome` | string | Busca parcial, case-insensitive |
| `email` | string | Busca parcial, case-insensitive |
| `papel` | string | Um valor de `UserRole` |
| `ativo` | boolean | `true` ou `false` |
| `page` | integer | Página atual |
| `per_page` | integer | 1 a 100; padrão 15 |

Resposta `200 OK` com paginação e envelope `data`/`meta`/`links`.

Ordenação padrão: `id` ascendente.

#### POST `/usuarios`

Request:

```json
{
  "nome": "João Pereira",
  "email": "joao@exemplo.com",
  "senha": "senha12345",
  "senha_confirmation": "senha12345",
  "papel": "autor"
}
```

Regras:

- Admin pode criar qualquer papel, inclusive `administrador`.
- Usuário criado nasce ativo.
- Normaliza o e-mail para minúsculas.

Resposta `201 Created` com o usuário criado.

#### GET `/usuarios/{usuario}`

Resposta `200 OK` com o usuário ou `404`.

#### PUT/PATCH `/usuarios/{usuario}`

Request:

```json
{
  "nome": "João P. Pereira",
  "email": "joao.pereira@exemplo.com",
  "papel": "editor"
}
```

Regras:

- Todos os campos são obrigatórios.
- E-mail único, ignorando o próprio usuário.
- Não permite alterar a senha.
- Não permite alterar o status; use ativar/desativar.
- Admin não pode alterar o próprio papel (`403`).
- Admin não pode rebaixar o último administrador ativo (`422`).

Resposta `200 OK` com o usuário atualizado.

#### PATCH `/usuarios/{usuario}/ativar`

- Ativa o usuário.
- Idempotente.
- Resposta `200 OK` com o usuário atualizado.

#### PATCH `/usuarios/{usuario}/desativar`

- Desativa o usuário.
- Revoga todos os tokens do usuário.
- Impede desativar a si mesmo (`403`).
- Impede desativar o último administrador ativo (`422`).
- Idempotente para usuário já inativo, mantendo a revogação de tokens.
- Resposta `200 OK` com o usuário atualizado.

## 7. Regras de negócio

### 7.1 Cadastro

- Papel fixo `leitor`.
- Usuário nasce ativo.
- Senha armazenada com hash pelo cast do model.
- E-mail normalizado para minúsculas.
- Token criado após o cadastro.

### 7.2 Login

- Busca por e-mail normalizado.
- Verifica a senha com `Hash::check`.
- Verifica `is_active`.
- Retorna mensagem genérica para credenciais inválidas.
- Não registra senha, token ou e-mail em logs.

### 7.3 Usuário inativo

- Não pode autenticar.
- Tokens existentes são revogados na desativação.
- O middleware `EnsureUserIsActive` bloqueia qualquer token remanescente.
- Redefinição de senha não reativa o usuário.

### 7.4 Tokens

- Sem expiração (`config/sanctum.php` com `expiration => null`).
- Logout revoga apenas o token atual.
- Desativação revoga todos os tokens.
- Redefinição de senha revoga todos os tokens.
- Troca de senha revoga os demais tokens e mantém o atual.

### 7.5 Administrador

- Apenas `administrador` gerencia usuários.
- Não pode alterar o próprio papel nem desativar a si mesmo (`403`).
- O sistema nunca deve ficar sem ao menos um administrador ativo; rebaixar ou desativar o último
  administrador ativo retorna `422`.
- O admin inicial é criado pelo seeder via variáveis de ambiente.

### 7.6 Recuperação de senha

- Resposta de solicitação sempre genérica.
- Link do e-mail aponta para `GET /api/v1/autenticacao/redefinir-senha`, rota nomeada `password.reset`.
- O token de redefinição é de uso único.
- Após redefinir, todos os tokens de acesso são revogados.
- O corpo do e-mail é traduzido para pt-BR em `lang/pt_BR.json`.

### 7.7 Rate limiting

| Endpoint | Limite |
| --- | --- |
| `POST /autenticacao/entrar` | 6 requisições por minuto por IP |
| `POST /autenticacao/esqueci-senha` | 6 requisições por minuto por IP |
| `POST /autenticacao/cadastro` | 10 requisições por minuto por IP |
| Demais rotas autenticadas | 60 requisições por minuto por usuário ou IP (`throttleApi`) |

## 8. Validação

### 8.1 Campos

| Campo | Regras |
| --- | --- |
| `nome` | `required`, `string`, `max:255` |
| `email` | `required`, `email`, `max:255`, `unique` quando aplicável; normalizado em `prepareForValidation()` |
| `senha` | `required`, `string`, `min:8`, `confirmed`, `different:senha_atual` na troca de senha |
| `senha_atual` | `required`, `current_password:sanctum` |
| `papel` | `required`, `Rule::enum(UserRole::class)` |
| `ativo` | `sometimes`, `boolean` |
| `token` | `required`, `string` |
| `page` | `sometimes`, `integer`, `min:1` |
| `per_page` | `sometimes`, `integer`, `min:1`, `max:100` |

### 8.2 Mensagens

- Configurar `APP_LOCALE=pt_BR` e `APP_FALLBACK_LOCALE=en` no `.env.example`.
- Criar traduções em `lang/pt_BR/validation.php`, `lang/pt_BR/passwords.php`, `lang/pt_BR/auth.php`
  e `lang/pt_BR.json` (corpo do e-mail de redefinição).
- As mensagens de negócio dos Services e Controllers serão escritas diretamente em pt-BR.
- O fuso horário da aplicação será `America/Sao_Paulo`.

## 9. Arquitetura e organização

Fluxo principal:

```text
Request -> FormRequest (validação) -> Controller -> Policy (autorização)
        -> Service (regra de negócio) -> Model (Eloquent) -> Resource (resposta)
```

- Controllers não acessam o banco diretamente.
- Toda regra de negócio fica nos Services.
- Não haverá camada Repository.
- Autorização via Policies.
- Validação via Form Requests.
- Serialização via API Resource.
- Injeção de dependência via construtor.
- Sem `try/catch` nos Controllers; o tratamento global fica no `bootstrap/app.php`.
- `App\Http\Controllers\Controller` usará o trait `AuthorizesRequests` para `$this->authorize(...)`.
- Filtros parciais de `nome`/`email` usam `whereLike(..., caseSensitive: false)`.

### 9.1 Classes previstas

```text
app/Enums/UserRole.php
app/Http/Controllers/Api/V1/AutenticacaoController.php
app/Http/Controllers/Api/V1/PerfilController.php
app/Http/Controllers/Api/V1/UsuarioController.php
app/Http/Middleware/EnsureUserIsActive.php
app/Http/Requests/Autenticacao/CadastroRequest.php
app/Http/Requests/Autenticacao/EntrarRequest.php
app/Http/Requests/Autenticacao/EsqueciSenhaRequest.php
app/Http/Requests/Autenticacao/RedefinirSenhaRequest.php
app/Http/Requests/Autenticacao/ValidarRedefinicaoRequest.php
app/Http/Requests/Perfil/AtualizarPerfilRequest.php
app/Http/Requests/Perfil/AlterarSenhaRequest.php
app/Http/Requests/Usuario/ListarUsuariosRequest.php
app/Http/Requests/Usuario/StoreUsuarioRequest.php
app/Http/Requests/Usuario/UpdateUsuarioRequest.php
app/Http/Resources/UserResource.php
app/Policies/UserPolicy.php
app/Services/AuthService.php
app/Services/UserService.php
app/OpenApi/OpenApi.php
```

### 9.2 Services

`AuthService`:

- `cadastrar(array $dados): array`
- `entrar(array $dados): array`
- `sair(User $usuario): void`
- `solicitarRedefinicao(string $email): void`
- `validarTokenRedefinicao(string $email, string $token): void`
- `redefinirSenha(array $dados): void`

`UserService`:

- `listar(array $filtros): LengthAwarePaginator`
- `criar(array $dados): User`
- `atualizar(User $usuario, array $dados): User`
- `atualizarPerfil(User $usuario, array $dados): User`
- `alterarSenha(User $usuario, array $dados): void`
- `ativar(User $usuario): User`
- `desativar(User $usuario): User`

## 10. Rotas

`routes/api.php`:

```php
Route::prefix('v1')->group(function () {
    Route::prefix('autenticacao')->group(function () {
        Route::post('cadastro', [AutenticacaoController::class, 'cadastro']);
        Route::post('entrar', [AutenticacaoController::class, 'entrar']);
        Route::post('esqueci-senha', [AutenticacaoController::class, 'esqueciSenha']);
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
```

## 11. Tratamento global de exceções

Em `bootstrap/app.php`, manter o retorno JSON para `api/*` e padronizar:

| Exceção | Status | Resposta |
| --- | --- | --- |
| `ValidationException` | 422 | `mensagem` + `errors` |
| `AuthenticationException` | 401 | `mensagem` |
| `AuthorizationException` | 403 | `mensagem` |
| `ModelNotFoundException` | 404 | `mensagem` |
| `HttpExceptionInterface` | status da exceção | `mensagem` |
| Demais exceções | 500 | `mensagem` genérica |

Regras de negócio usam `abort(403, '...')`, `abort(404, '...')` ou `ValidationException` conforme o caso.
Nenhum detalhe interno deve ser exposto no erro 500.

## 12. Documentação com L5-Swagger

- Instalar `darkaonline/l5-swagger` (versão 11.x, compatível com Laravel 13).
- Publicar `config/l5-swagger.php`.
- Swagger UI em `/api/documentation`.
- JSON OpenAPI em `/docs` (servido pela rota `l5-swagger.default.docs`).
- Gerar documentação com `php artisan l5-swagger:generate`.
- Título: `OpenContent API`.
- Versão: `1.0.0`.
- Servidor: `/api/v1` (URL relativa ao host da documentação).
- Security scheme: `bearerAuth` do tipo `http`, scheme `bearer`, bearerFormat `Sanctum`.
- Documentar todos os endpoints, requests, respostas, códigos de erro e exemplos usando atributos
  PHP do `swagger-php` 6 (`#[OA\...]`), não annotations em docblock.
- Tags: `Autenticação`, `Perfil`, `Usuários`.
- Endpoints protegidos marcados com `security: [["bearerAuth" => []]]`.
- Adicionar `L5_SWAGGER_GENERATE_ALWAYS=true` ao `.env.example`.

## 13. Mensagens e LGPD

- Não registrar senha, token, e-mail, nome completo ou dados pessoais em logs.
- Logs devem ser genéricos e sem identificação do usuário.
- Não retornar hash de senha em nenhuma resposta.
- Respostas de recuperação de senha não confirmam se o e-mail existe.
- Tokens de API não devem ser retornados em logs ou mensagens de erro.

## 14. Arquivos afetados

### 14.1 Arquivos existentes que serão alterados

```text
composer.json
composer.lock
bootstrap/app.php
config/app.php
app/Http/Controllers/Controller.php
app/Models/User.php
app/Providers/AppServiceProvider.php
database/factories/UserFactory.php
database/seeders/DatabaseSeeder.php
.env.example
AGENTS.md
```

### 14.2 Arquivos novos

```text
docs/auth-e-user/spec.md
routes/api.php
database/migrations/XXXX_XX_XX_XXXXXX_add_role_and_is_active_to_users_table.php
config/sanctum.php
config/l5-swagger.php
lang/pt_BR/validation.php
lang/pt_BR/passwords.php
lang/pt_BR/auth.php
lang/pt_BR.json
```

Mais as classes listadas na seção 9.1 e a migration do Sanctum (`personal_access_tokens`).

### 14.3 Arquivos que não serão alterados

```text
.env
routes/web.php
config/auth.php
config/database.php
tests/*
```

## 15. Ordem de implementação

1. Criar este `spec.md`.
2. Atualizar `AGENTS.md` para as convenções Laravel.
3. Instalar Sanctum e L5-Swagger.
4. Configurar rotas de API e middleware.
5. Criar `UserRole`, alterações no model, migration, factory e seeder.
6. Criar Policy e middleware de usuário ativo.
7. Criar Form Requests e UserResource.
8. Criar `AuthService` e `UserService`.
9. Criar Controllers.
10. Configurar rotas versionadas.
11. Configurar traduções pt-BR e tratamento de exceções.
12. Configurar e anotar OpenAPI.
13. Rodar Pint, `route:list` e geração do Swagger.
14. Entregar as instruções de migration e seed ao desenvolvedor.

## 16. Comandos de implementação

O host não possui PHP instalado; os comandos devem rodar via Sail.

```bash
./vendor/bin/sail artisan install:api --without-migration-prompt
./vendor/bin/sail artisan vendor:publish --tag=sanctum-config
./vendor/bin/sail composer require darkaonline/l5-swagger
./vendor/bin/sail artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

`install:api` instala o Sanctum, publica a migration `personal_access_tokens`, cria
`routes/api.php` e registra a rota de API no `bootstrap/app.php`. O parâmetro
`--without-migration-prompt` impede a execução de migrations.

Atenção: não executar `migrate`, `db:seed` ou `database update` pelo agente.

## 17. Verificação

- `./vendor/bin/sail artisan route:list --path=api`
- `./vendor/bin/sail bin pint --test`
- `./vendor/bin/sail artisan l5-swagger:generate`
- `./vendor/bin/sail artisan test` (somente testes existentes; nenhum teste novo será criado)

Entregar ao desenvolvedor:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

## 18. Riscos e observações

- O `AGENTS.md` atual está orientado a .NET e será reescrito para Laravel nesta task.
- O host não tem PHP; toda execução depende do Docker/Sail.
- A migration e o seeder não serão executados pelo agente, conforme regra do projeto.
- O link de redefinição aponta para a API e retorna token/e-mail em JSON. Quando houver frontend,
  o destino pode ser alterado para uma tela de redefinição.
- Sem verificação de e-mail, a troca de e-mail no perfil é livre nesta versão.
- O `.env.example` será alinhado ao PostgreSQL do `compose.yaml` (hoje ainda aponta para SQLite).
- O fuso horário `America/Sao_Paulo` será configurado em `config/app.php`.
- Sem testes automatizados nesta task, a validação será feita via `route:list`, Pint, Swagger e
  testes manuais com `curl`.
