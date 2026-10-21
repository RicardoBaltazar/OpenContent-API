# AGENTS.md

Este arquivo documenta regras, contextos e especificacoes tecnicas do sistema, com o objetivo de fornecer contexto para agentes de IA e desenvolvedores.

## Stack e Ambiente

- Backend em **Laravel 13** com **PHP 8.3+**, PostgreSQL, Redis e Laravel Sail.
- O host nao possui PHP instalado; todos os comandos devem rodar via Sail (`./vendor/bin/sail ...`).
- Formatacao de codigo com **Laravel Pint**.

## Arquitetura e Regras de Criacao de Classes

- O sistema segue **Controller + Service + Model + Enum + Policy**, sem camada Repository.
- **Controllers** devem ser o mais simples possivel: recebem a requisicao, delegam para o Service e retornam Resources/respostas padronizadas.
- Toda regra de negocio deve ficar nas classes da camada **Service**.
- **Models** representam as entidades e concentram casts, relacoes e configuracoes do Eloquent.
- **Controllers** nunca devem retornar Models diretamente; sempre usar **API Resources**.
- Validacao de entrada via **Form Requests**.
- Autorizacao via **Policies** nativas do Laravel.
- Injecao de dependencia via construtor, usando o container do Laravel. Nunca instanciar classes de producao com `new`.
- Estrutura de pastas em `app/`:
    - `Enums/` - enums do dominio
    - `Http/Controllers/Api/V1/` - controllers versionados
    - `Http/Requests/` - validacao de entrada
    - `Http/Resources/` - serializacao de resposta
    - `Http/Middleware/` - middlewares
    - `Models/` - entidades Eloquent
    - `Policies/` - autorizacao
    - `Services/` - regras de negocio
    - `OpenApi/` - documentacao OpenAPI
- Services devem ter poucos metodos privados, apenas quando realmente necessario.

## Tratamento de Excecoes

- O tratamento e centralizado no `bootstrap/app.php` via `withExceptions`.
- Para erros controlados (validacao, nao encontrado, conflito, etc.), usar `abort(403, '...')`, `abort(404, '...')` ou `ValidationException`.
- O retorno JSON para `api/*` deve ser padronizado:
    - `ValidationException` -> `422` com `mensagem` e `errors`
    - `AuthenticationException` -> `401` com `mensagem`
    - `AuthorizationException` -> `403` com `mensagem`
    - `ModelNotFoundException` -> `404` com `mensagem`
    - `HttpExceptionInterface` -> status da excecao com `mensagem`
    - Demais excecoes -> `500` com mensagem generica, sem vazar detalhes internos
- Controllers nunca devem conter `try/catch`.

## Padrao de Commits

Os commits devem seguir o padrao **Conventional Commits** em portugues.

**Preferir sempre uma unica linha de commit:**

```text
feat: implementa visualizacao por grupo com busca de representantes e sistema de bloqueio
```

**Usar topicos APENAS se absolutamente necessario:**

```text
feat: implementa visualizacao por grupo de empresas

- Adiciona busca de representantes de grupo
```

**Tipos de commit:**

- `feat`: nova funcionalidade
- `fix`: correcao de bug
- `refactor`: refatoracao de codigo
- `perf`: melhorias de performance
- `docs`: documentacao
- `style`: formatacao de codigo
- `test`: adicao ou correcao de testes
- `chore`: tarefas de manutencao

## Logs e LGPD

- Nunca registrar senha, token, e-mail, nome completo, CPF, CNPJ, endereco ou qualquer dado pessoal identificavel em logs.
- Preferir logs genericos ou mascarados quando necessario para depuracao.
- Nunca retornar hash de senha, tokens de terceiros ou dados sensiveis em respostas da API.

## Database e Migrations

- Nao consulte, leia ou manipule dados do banco diretamente (ex.: queries SQL, ferramentas externas ou scripts).
- Qualquer consulta a dados deve ser feita apenas por endpoints/rotas da aplicacao.
- E permitido criar migrations (`./vendor/bin/sail artisan make:migration ...`), mas nunca executa-las no banco.
- Nunca executar `migrate`, `db:seed` ou `database update`. Entregar esses comandos ao desenvolvedor.
- Nao salvar scripts SQL em arquivos no projeto. Scripts, quando necessarios, devem ser exibidos apenas no chat.

## Regras de Endpoints

- Todos os endpoints devem ser criados em portugues (pt-BR) na URL.
- A API e versionada em `/api/v1`.
- Respostas de sucesso usam envelope `data`/`mensagem`; listagens incluem `links` e `meta`.
- Erros usam `mensagem` e, em validacao, tambem `errors`.
- Campos JSON devem ser em portugues.

## Regras de Operacao do Agente

- Realizar apenas o que estiver no planejamento aprovado. Nao implementar funcionalidades ou logicas nao planejadas.
- Nao alterar arquivos existentes sem aprovacao durante o planejamento.
- Sempre escrever o menor volume de codigo possivel, da forma mais simples, respeitando a arquitetura.
- Apos concluir uma task, rodar Pint e as verificacoes planejadas (`route:list`, testes existentes, etc.).

## Regras de Comentarios e Documentacao

- Nao adicionar comentarios soltos no codigo.
- Documentar metodos publicos com PHPDoc:

```php
/**
 * Retorna o inventario de emissoes de home-office para um grupo de empresas.
 *
 * @param  string  $cnpj  CNPJ da empresa matriz.
 * @return array<string, mixed>
 */
public function listar(string $cnpj): array
{
    // ...
}
```

- Manter a documentacao OpenAPI atualizada usando atributos PHP do `swagger-php` (`#[OA\...]`).
- Nao deixar `use` no topo dos arquivos quando nao estiverem sendo utilizados.
