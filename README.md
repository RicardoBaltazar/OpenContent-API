# OpenContent

Headless CMS open source, API-first e extensível, desenvolvido para servir como núcleo de gerenciamento de conteúdo para aplicações web, mobile e outros sistemas.

O OpenContent não possui um frontend obrigatório. A API é o principal produto do sistema, permitindo que diferentes aplicações consumam e publiquem conteúdo.

## Sumário

- [Sobre o projeto](#sobre-o-projeto)
- [Funcionalidades](#funcionalidades)
- [Stack](#stack)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Acessos](#acessos)
- [Administrador inicial](#administrador-inicial)
- [Recuperação de senha em desenvolvimento](#recuperação-de-senha-em-desenvolvimento)
- [Comandos úteis](#comandos-úteis)
- [Licença](#licença)

## Sobre o projeto

O OpenContent tem como objetivo fornecer uma plataforma genérica para gerenciamento de conteúdo, usuários, mídia e publicação.

O sistema deve permitir que uma empresa ou desenvolvedor utilize o CMS como backend para diferentes tipos de aplicações, como:

- sites institucionais;
- blogs;
- portais;
- aplicativos mobile;
- lojas virtuais;
- comunidades;
- plataformas de conteúdo;
- sistemas internos.

## Funcionalidades

### Autenticação

- Cadastro público de usuários com papel `leitor`.
- Login e logout com tokens pessoais do Laravel Sanctum.
- Bloqueio de usuários inativos.
- Revogação de tokens no logout, na desativação, na redefinição e na troca de senha.

### Recuperação de senha

- Solicitação de link de redefinição por e-mail.
- Validação do token de redefinição.
- Redefinição de senha com revogação de todos os tokens de acesso.

### Perfil

- Visualização do próprio perfil.
- Atualização de nome e e-mail.
- Troca de senha com validação da senha atual.

### Usuários

- Listagem administrativa com filtros por nome, e-mail, papel e status.
- Paginação configurável.
- Criação, detalhamento e edição de usuários.
- Ativação e desativação de usuários.
- Proteção contra rebaixamento ou desativação do último administrador ativo.

### Papéis

- `administrador`
- `editor`
- `autor`
- `leitor`

Nesta versão, apenas o papel `administrador` possui permissões administrativas.

### API e documentação

- API versionada em `/api/v1`.
- Respostas padronizadas em `data`/`mensagem` e erros em `mensagem`/`errors`.
- Rate limiting nos endpoints públicos e nas rotas autenticadas.
- Documentação OpenAPI com Swagger UI.
- Geração da documentação via `./vendor/bin/sail artisan l5-swagger:generate`.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Backend | Laravel 13 / PHP 8.3+ |
| Banco de dados | PostgreSQL 18 |
| Cache e filas | Redis |
| Autenticação | Laravel Sanctum |
| Documentação | L5-Swagger |
| Ambiente | Docker + Laravel Sail |
| Formatação | Laravel Pint |

## Requisitos

- Git.
- Docker.
- Docker Compose v2 ou superior.

O PHP e o Composer não precisam estar instalados no host. Caso não tenha Composer localmente, as dependências podem ser instaladas via Docker.

## Instalação

Clone o repositório:

```bash
git clone <url-do-repositorio> opencontent
cd opencontent
```

Crie o arquivo de ambiente:

```bash
cp .env.example .env
```

Instale as dependências do Composer via Docker:

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -e COMPOSER_HOME=/tmp/composer \
  -v "$(pwd):/app" \
  -w /app \
  composer:2 install --no-interaction --prefer-dist
```

Suba os containers:

```bash
./vendor/bin/sail up -d
```

Gere a chave da aplicação:

```bash
./vendor/bin/sail artisan key:generate
```

Execute as migrations:

```bash
./vendor/bin/sail artisan migrate
```

Configure o administrador inicial no `.env`:

```dotenv
ADMIN_NAME="Administrador"
ADMIN_EMAIL=admin@exemplo.com
ADMIN_PASSWORD=uma-senha-segura
```

Crie o administrador:

```bash
./vendor/bin/sail artisan db:seed
```

O seeder cria o administrador somente quando `ADMIN_EMAIL` e `ADMIN_PASSWORD` estiverem preenchidos e não sobrescreve a senha de um usuário já existente.

## Acessos

Com `APP_PORT=8000`:

- API: `http://localhost:8000/api/v1`
- Swagger UI: `http://localhost:8000/api/documentation`
- JSON OpenAPI: `http://localhost:8000/docs`

## Administrador inicial

O administrador inicial é criado pelo seeder a partir das variáveis `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD` definidas no `.env`.

Mantenha o arquivo `.env` fora do versionamento e não utilize as credenciais de exemplo em produção.

## Recuperação de senha em desenvolvimento

O ambiente de desenvolvimento usa `MAIL_MAILER=log`. O link de redefinição é gravado em `storage/logs/laravel.log`.

Para acompanhar os logs:

```bash
./vendor/bin/sail artisan pail
```

## Comandos úteis

```bash
./vendor/bin/sail up -d
./vendor/bin/sail down
./vendor/bin/sail artisan route:list --path=api
./vendor/bin/sail artisan l5-swagger:generate
./vendor/bin/sail bin pint
./vendor/bin/sail artisan test
```

## Licença

Este projeto está licenciado sob a licença MIT. Consulte o arquivo [LICENSE](LICENSE) para mais detalhes.
