# Pano — Developer Documentation

This is the complete developer reference for the **Pano** nano-framework.

It explains how to build applications on top of Pano: every concept, every contract,
every public API, and every convention you need to write working code.

> For philosophy and principles, read [`MANIFESTO.md`](MANIFESTO.md).  
> For internal system design and runtime model, read [`ARCHITECTURE.md`](ARCHITECTURE.md).  
> **This document is for developers who want to *build* with Pano.**

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Requirements & Installation](#2-requirements--installation)
3. [Quick Start](#3-quick-start)
4. [Project Structure](#4-project-structure)
5. [Mental Model — The Three Layers](#5-mental-model--the-three-layers)
6. [The Request Lifecycle](#6-the-request-lifecycle)
7. [Configuration](#7-configuration)
8. [Environment Variables](#8-environment-variables)
9. [Helper Functions](#9-helper-functions)
10. [Modules](#10-modules)
11. [Routing](#11-routing)
12. [Handlers](#12-handlers)
13. [Interceptors](#13-interceptors)
14. [The Request Object](#14-the-request-object)
15. [Responses](#15-responses)
16. [Views & Templates](#16-views--templates)
17. [The Bag (Data Container)](#17-the-bag-data-container)
18. [Logging](#18-logging)
19. [Exceptions & Error Handling](#19-exceptions--error-handling)
20. [CLI Commands](#20-cli-commands)
21. [Enums Reference](#21-enums-reference)
22. [Replacing the Foundation](#22-replacing-the-foundation)
23. [Conventions & Best Practices](#23-conventions--best-practices)
24. [Full Example — Building a Module](#24-full-example--building-a-module)

---

## 1. Introduction

Pano is a minimal, dependency-free PHP execution foundation. It is **not** a
batteries-included application framework. It deliberately leaves architectural
decisions to you and provides only the strict runtime needed to:

- accept a request (HTTP or CLI),
- resolve which module and handler should process it,
- run an interceptor pipeline,
- execute a handler,
- resolve and dispatch a response.

Everything else — ORMs, auth systems, mailers, queues — is yours to build or
bring in. Pano gives you the rails; you drive the train.

**Important:** Pano is distributed as a **pure library**.  
Application-level files (`index.php`, `pano` CLI, `config/`, default modules, `.env.example`)
are **not** included in the framework package. Use the official skeleton
[`pano-php/pano`](https://github.com/pano-php/pano) for a ready-to-run application layout.

Pano is built around three concepts that you must understand before writing code:

- **Kernel** — abstract contracts (the `Base*` classes). Never changes behavior.
- **Foundation** — the default concrete implementations (the `Pano\Foundation\*` classes). Replaceable.
- **Modules** — your application code, organized as isolated units.

---

## 2. Requirements & Installation

### Requirements

- **PHP >= 8.2**
- Composer
- A web server (Apache with `mod_rewrite`, Nginx, or the PHP built-in server for development)

### Installation (Library)

```bash
composer require pano-php/pano-framework
```

Pano has **zero** runtime Composer dependencies of its own.

### Recommended: Use the Official Skeleton

```bash
composer create-project pano-php/pano my-app
cd my-app
```

This gives you a complete working layout with `public/index.php`, CLI entry point,
`config/`, a `Default` module, and tests.

### Running the development server (Skeleton)

```bash
php -S localhost:8000 -t public
```

Then visit `http://localhost:8000`.

---

## 3. Quick Start

Because Pano is a pure library, you bootstrap it yourself (or let the skeleton do it).

### Minimal HTTP entry point

```php
<?php
// public/index.php

define('PANO_STARTED', microtime(true));
define('BASE_PATH', rtrim(__DIR__ . '/../', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

require BASE_PATH . 'vendor/autoload.php';

(new \Pano\Foundation\Boot())->run($_SERVER);
```

### Minimal CLI entry point

```php
#!/usr/bin/env php
<?php
// pano (executable)

define('PANO_STARTED', microtime(true));
define('BASE_PATH', rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

require BASE_PATH . 'vendor/autoload.php';

(new \Pano\Foundation\Boot())->run($argv, true);
```

`Boot::run(array $data, bool $cli = false)`:

- Web → pass `$_SERVER`
- CLI → pass `$argv` and set `$cli = true`

That single call:

1. loads `.env` (via constructor),
2. configures error reporting and timezone,
3. builds a `Request` / `CLIRequest`,
4. resolves the target module,
5. runs that module’s router,
6. dispatches the matched handler / command,
7. sends the response.

---

## 4. Project Structure

### Framework package (library only)

```text
vendor/pano-php/pano-framework/
└── src/
    ├── helpers.php
    ├── Kernel/          # Abstract contracts (Base*)
    └── Foundation/      # Default concrete implementations
```

There is **no** application code, no `config/`, no `index.php`, and no default module inside the library.

### Recommended application layout (Skeleton)

```text
my-app/
├── pano                      # CLI entry point (executable)
├── public/
│   ├── index.php             # Web front controller
│   └── .htaccess
├── config/
│   ├── app.php
│   └── modules.php
├── modules/                  # Your application modules (namespace Modules\)
│   └── Default/
│       ├── DefaultModule.php
│       ├── Handlers/
│       ├── Interceptors/
│       ├── Commands/
│       └── Views/
├── tests/
├── .env
├── .env.example
└── composer.json
```

Two constants must be defined at the very start of every entry point:

| Constant       | Meaning                                              |
|----------------|------------------------------------------------------|
| `PANO_STARTED` | Request start timestamp (microtime), useful for timing |
| `BASE_PATH`    | Absolute path to the project root (with trailing `/`) |

Everything (config, `.env`, module paths) is resolved relative to `BASE_PATH`.

---

## 5. Mental Model — The Three Layers

### Layer 1 — Kernel (`Pano\Kernel\*`)

Abstract classes (`BaseBoot`, `BaseModule`, `BaseRouter`, `BaseRequest`, …) and enums.  
Contracts only. No application behavior, no dependencies.

You **never** instantiate Kernel classes directly. You extend them or use the Foundation implementations.

### Layer 2 — Foundation (`Pano\Foundation\*`)

Default concrete implementations (`final` classes). Provide working HTTP/CLI parsing,
routing, responses, views, logging, etc.

The Foundation is **replaceable**. You can implement the Kernel contracts yourself.

### Layer 3 — Modules (your code)

Self-contained units that own routes, handlers, interceptors, commands and views.  
In the skeleton they live under the `Modules\` namespace (e.g. `Modules\Blog\BlogModule`).

---

## 6. The Request Lifecycle

```text
Entry point (index.php / pano)
   │
   ▼
Boot::__construct()
   ├── envLoader()            → parses .env into $_ENV / $_SERVER
   ├── debug()                → sets error_reporting & display_errors
   └── timezone               → date_default_timezone_set(config('app.timezone'))
   │
   ▼
Boot::run($data, $cli)
   └── dispatcher(Request|CLIRequest, $data)
   │
   ▼
dispatcher()
   ├── new Request|CLIRequest
   ├── request->getModule()   → resolves module key (path or subdomain)
   ├── config('modules.X')    → maps key to module class
   ├── new $Module($request)
   └── $module->routes()->handle()
   │
   ▼
Router::handle()
   ├── match route / command
   ├── run interceptors (onRequest → handler → onResponse)
   └── $response->send()
```

Any uncaught throwable is converted by `Response::exception()` and sent.  
No request should crash the process uncaught.

---

## 7. Configuration

All configuration lives in `config/*.php` (relative to `BASE_PATH`).  
Each file returns an array and is loaded lazily by the `config()` helper.

### Minimum required `config/app.php`

```php
<?php

return [
    'name'     => env('APP_NAME', 'Pano'),
    'env'      => env('APP_ENV', 'local'),
    'key'      => env('APP_KEY', null),
    'debug'    => env('APP_DEBUG', false),
    'url'      => env('APP_URL', null),
    'resolver' => env('MODULE_RESOLVER', 'path'),   // "path" | "subdomain"
    'timezone' => env('APP_TIMEZONE', 'UTC'),
];
```

| Key        | Type         | Default  | Description                                      |
|------------|--------------|----------|--------------------------------------------------|
| `name`     | string       | `Pano`   | Application display name                         |
| `env`      | string       | `local`  | Current environment                              |
| `key`      | string\|null | `null`   | Application secret key                           |
| `debug`    | bool         | `false`  | Show detailed errors                             |
| `url`      | string\|null | `null`   | Base application URL (used by `url()` & subdomain resolver) |
| `resolver` | string       | `path`   | Module resolution strategy                       |
| `timezone` | string       | `UTC`    | Default timezone                                 |

### `config/modules.php`

Maps an incoming module key to a module class:

```php
<?php

return [
    ''     => \Modules\Default\DefaultModule::class,   // default / root
    'blog' => \Modules\Blog\BlogModule::class,
];
```

The empty-string key (`''`) is the module that serves the root when using path-based resolution.

### Reading config

```php
config('app.debug');                 // bool
config('app.name');                  // string
config('modules.blog');              // class name or null
config('app.missing', 'fallback');   // with default
```

---

## 8. Environment Variables

Pano ships with its own tiny `.env` parser (no third-party package).

```ini
APP_NAME=MyApp
APP_ENV=local
APP_KEY=base64:your-key-here
APP_DEBUG=true
APP_URL=https://example.com
MODULE_RESOLVER=path
APP_TIMEZONE=UTC
```

Values `true` / `false` / `null` (case-insensitive) and numeric strings are parsed automatically.  
Quotes around values are stripped.

```php
env('APP_NAME', 'Pano');
env('APP_DEBUG', false);
```

---

## 9. Helper Functions

Always available (autoloaded via `composer.json`):

| Function | Description |
|----------|-------------|
| `env(string $key, mixed $default = null): mixed` | Read environment variable |
| `config(string $key, mixed $default = null): mixed` | Read config with dot notation |
| `url(string $path): string` | Absolute URL using `app.url` |
| `currentUrl(): string` | Absolute URL of the current request |
| `dd(...$args): void` | Dump and die (CLI-colored or HTML-styled) |

---

## 10. Modules

A module is a `final readonly` class extending `Pano\Kernel\BaseModule`.  
It **must** implement three methods:

```php
public function routes(): BaseRouter;
public function view(): BaseView;
public function log(): BaseLogger;
```

### Path helpers (via reflection)

```php
$this->viewPath();   // .../Modules/Blog/Views
$this->filePath();   // .../Modules/Blog/Files
$this->logPath();    // .../Modules/Blog/Logs
$this->path();       // .../Modules/Blog
$this->name();       // "BlogModule"
```

Register every module in `config/modules.php`.

### Module Resolution

Controlled by `config('app.resolver')` / `MODULE_RESOLVER`:

**`path` (default)**  
First URL segment = module key. Rest = route path.

| URL              | Module key | Route path   |
|------------------|------------|--------------|
| `/blog/posts/12` | `blog`     | `/posts/12`  |
| `/`              | `''`       | `/`          |

**`subdomain`**  
Subdomain = module key. Root domain is taken from `APP_URL`.

| Host                       | Module key |
|----------------------------|------------|
| `blog.example.com`         | `blog`     |
| `api.v2.example.com`       | `api.v2`   |
| `example.com`              | `''`       |

If the resolved key has no matching entry in `config/modules.php`, Pano throws  
`No module found for '<name>'`.

---

## 11. Routing

Routes are registered inside the module’s `routes()` method:

```php
$router = new \Pano\Foundation\Router($this->request, $this);

$router->get('/posts', PostHandler::class, 'index');
$router->get('/posts/[id]', PostHandler::class, 'show');
$router->post('/posts', PostHandler::class, 'store', [AuthInterceptor::class]);
$router->put('/posts/[id]', PostHandler::class, 'update');
$router->delete('/posts/[id]', PostHandler::class, 'destroy');

$router->command('blog:publish', PublishCommand::class);

return $router;
```

### Route parameters

| Syntax   | Meaning                                 |
|----------|-----------------------------------------|
| `[id]`   | Required segment                        |
| `[id?]`  | Optional (must be the **last** segment) |
| `[id*]`  | Catch-all (must be the **last** segment)|

Parameters are passed to the handler method **in declaration order**.

---

## 12. Handlers

A handler extends `Pano\Kernel\BaseHandler`.  
Each public action method **must** declare a return type of `BaseResponse` (or subclass).

```php
final class PostHandler extends BaseHandler
{
    public function index(): Response
    {
        return Response::json(['posts' => []]);
    }

    public function show($id): Response
    {
        return Response::json(['id' => $id]);
    }

    public function store(): Response
    {
        $data = $this->request->getData();
        return Response::json(['created' => true], HttpStatusEnum::CREATED);
    }
}
```

`$this->request` and `$this->module` are available.

### Method override

On `POST` requests Pano respects:

- header `X-HTTP-Method-Override`, or
- body field `_method`

so HTML forms can simulate `PUT` / `DELETE` / `PATCH`.

---

## 13. Interceptors

Interceptors are the **cross-cutting** layer: auth, rate limiting, logging,
request transformation, response decoration. They run **around** a handler.

An interceptor extends `BaseInterceptor`:

```php
namespace Modules\Blog\Interceptors;

use Pano\Kernel\BaseInterceptor;
use Pano\Kernel\BaseResponse;

final class AuthInterceptor extends BaseInterceptor
{
    public function onRequest(): void
    {
        $token = $this->request->getHeaders()['authorization'] ?? null;
        $this->request->attributes->set('userId', $this->resolveToken($token));
    }

    public function onResponse(BaseResponse $response): BaseResponse
    {
        return $response->setHeader('X-Processed-By', 'Pano');
    }
}
```

### The interceptor contract

```php
abstract class BaseInterceptor
{
    public function __construct(public readonly BaseRequest $request) {}

    public function onRequest(): void {}                                // before the handler

    public function onResponse(BaseResponse $response): BaseResponse {  // after the handler
        return $response;
    }
}
```

Both methods are optional — override whichever you need.

### Execution order

For a route registered with `[A::class, B::class]`:

```text
A::onRequest()   →
  B::onRequest() →
    Handler::action()   (returns a Response)
  B::onResponse(response)   ←
A::onResponse(response)     ←
response->send()
```

So `onRequest` runs in **registration order** and `onResponse` runs in
**reverse order** — exactly like layered middleware (Russian-doll model).

### Sharing state with the handler

The request object is **shared** across all interceptors and the handler. Each
interceptor receives the same `$request` instance. Because the request exposes
a mutable `Bag` called `attributes`, this is the idiomatic channel for passing
data downstream:

```php
// in an interceptor
$this->request->attributes->set('user', $user);

// in the handler
$user = $this->request->attributes->get('user');
```

Use `$request->attributes` for things like the authenticated user, request IDs,
feature flags — anything the handler needs that interceptors computed.

### Attaching interceptors

Interceptors are attached **per route** as the 4th argument:

```php
$this->router->get('/dashboard', DashboardHandler::class, 'index', [
    AuthInterceptor::class,
    RateLimitInterceptor::class,
]);
```

A route with no interceptors simply omits the array.

---

## 14. The Request Object

The request is your window into everything the client sent. It is built by the
Foundation (`Request` for HTTP, `CLIRequest` for console) and extends the
abstract `BaseRequest`.

### Public API

```php
$request->getMethod(): HttpMethodEnum;
$request->getUrl(): string;                 // path without the module prefix
$request->getHost(): string;                // scheme://host
$request->getQuery(): string;               // raw query string
$request->getQueries(): array;              // parsed query params
$request->getHeaders(): array;              // all headers, keys lowercased
$request->getData(): string|array;          // request body / $_POST
$request->getFiles(): array;                // normalized $_FILES
$request->getSegments(): array;             // URL path segments
$request->attributes: Bag;                  // mutable shared state (see Bag)
```

### Method detection

The HTTP method comes from `REQUEST_METHOD` and is normalized to an
`HttpMethodEnum`. POST requests support **method override** via:

- the `X-HTTP-Method-Override` header, or
- a `_method` field in the POST body.

This lets you submit `PUT`/`DELETE` from plain HTML forms.

### Body parsing

`getData()` automatically decodes the body based on `Content-Type`:

- `$_POST` if present,
- `application/json` → decoded JSON array,
- `application/x-www-form-urlencoded` → parsed string,
- otherwise an empty array.

### File uploads

`getFiles()` returns `$_FILES` **normalized**. Multi-file inputs (e.g.
`<input name="photos[]">`) are restructured into an indexed list of file arrays,
so you always iterate a flat list:

```php
foreach ($request->getFiles()['photos'] ?? [] as $file) {
    move_uploaded_file($file['tmp_name'], $target);
}
```

### JSON expectation

```php
$request->expectsJson(): bool;   // true if the Accept header asks for JSON
```

This is what the default exception renderer uses to decide between an HTML and a
JSON error page.

### The `attributes` Bag

`$request->attributes` is a mutable `Bag` (see [The Bag](#17-the-bag-data-container))
carried with the request. It is the intended place for interceptors to leave
data for the handler — the user object, request-scoped values, etc.

---

## 15. Responses

A handler **must** return a `BaseResponse`. The Foundation provides the concrete
`Response` class with a fluent, factory-style API.

### Factory methods

```php
Response::make(mixed $body = null, HttpStatusEnum $status = OK, array $headers = []): self;
Response::json(array|object $data, HttpStatusEnum $status = OK, array $headers = []): self;
Response::text(string $text, HttpStatusEnum $status = OK, array $headers = []): self;
Response::html(string $html, HttpStatusEnum $status = OK, array $headers = []): self;
Response::stream(callable $callback, string $contentType = 'application/octet-stream', HttpStatusEnum $status = OK, array $headers = []): self;
Response::redirect(string $to, HttpStatusEnum $status = FOUND, array $headers = []): self;
Response::terminal(string $text, ResultCodeEnum $status = OK): self;   // for CLI
Response::exception(\Throwable $e, BaseRequest $request): self;
```

### Examples

```php
// JSON API
return Response::json(['status' => 'ok']);

return Response::json(
    ['error' => 'Not found'],
    HttpStatusEnum::NOT_FOUND
);

// Plain text / download
return Response::text('OK')
    ->setHeader('X-Custom', 'value');

// HTML from a view
return Response::html(
    $this->module->view()->layout('layout')->render('home')
);

// Redirect
return Response::redirect(url('/login'));

// Streaming a large payload
return Response::stream(function () {
    for ($i = 0; $i < 1000; $i++) {
        echo "line $i\n";
        flush();
    }
}, 'text/plain');

// CLI success / error
return Response::terminal('Done');                       // green
return Response::terminal('Failed', ResultCodeEnum::ERROR); // red
```

### Fluent mutation

Every mutator returns `$this`, so you can chain:

```php
return Response::json($data)
    ->setStatus(HttpStatusEnum::CREATED)
    ->setHeader('X-Request-Id', $id)
    ->setHeaders(['Cache-Control' => 'no-store']);
```

| Method | Description |
|---|---|
| `setStatus(HttpStatusEnum \| ResultCodeEnum)` | Set the status. |
| `setHeader(string $key, string $value)` | Add/overwrite a single header. |
| `setHeaders(array $headers)` | Add multiple headers. |
| `setBody(mixed $body)` | Replace the body (string, callable, etc.). |

### Sending

Calling `send()` writes the HTTP status line, headers, and body to the output
stream. It is **idempotent** — a response can only be sent once. The router
calls `send()` for you; you normally just `return` the response.

### Automatic error rendering

If a handler (or anything in the pipeline) throws, `Response::exception()` turns
the throwable into an appropriate response:

- a `BaseException` → rendered via its own `toArray()` / `toHtml()` methods,
- in CLI → a colored terminal message,
- for JSON clients → a JSON body,
- otherwise → an HTML page.

This is wired into the global handler, so you never need to wrap your handlers
in try/catch for rendering.

---

## 16. Views & Templates

Pano includes a tiny, fast template engine based on plain PHP files. No new
syntax to learn — views are `.php` files with full PHP available.

A view engine is obtained from the module:

```php
$view = $this->module->view();   // a Pano\Foundation\View scoped to Views/
```

### Passing data

```php
$html = $view->with(['name' => 'Pano', 'version' => '1.0'])
             ->render('home');
```

Keys become variables inside the template (`$name`, `$version`). `with()` merges
into previously set data.

### Layouts and sections

A view can declare a **layout**, which wraps it. Sections defined inside the
view are emitted into the layout.

`Views/layout.php`:

```php
<!DOCTYPE html>
<html>
<head><title><?php $this->section('title', 'Default Title') ?></title></head>
<body>
    <?php $this->section('content') ?>
</body>
</html>
```

`Views/home.php`:

```php
<?php $this->start('title') ?>Welcome<?php $this->end() ?>

<div class="card">
    Hello, <?= $this->e($name) ?>!
</div>
```

Rendering with a layout:

```php
$html = $view->with(['name' => 'Pano'])->layout('layout')->render('home');
```

When `render()` runs:

1. the `home` view is captured (its `start()`/`end()` blocks fill `sections`),
2. the captured output is stored as the `content` section,
3. the `layout` is captured and its `section()` calls print the filled sections.

> Sections cannot be nested. You must `end()` the current section before
> starting another.

### The `$view->section()` call

Inside a template, `$this->section('name', 'default')` **echoes** a previously
defined section (or the default). This is how layouts pull content out of views.

### Fragments (partials)

To include another template without a layout, use `fragment()`:

```php
<?php $this->fragment('partials/nav', ['items' => $menu]) ?>
```

Fragments inherit the parent view's data, merged with anything you pass.

### Escaping output

Always escape user-supplied data with `e()`:

```php
<p><?= $this->e($comment) ?></p>
```

`e()` runs `htmlspecialchars` with `ENT_QUOTES`, `ENT_SUBSTITUTE`, and UTF-8.
Non-stringable objects throw an `InvalidArgumentException`.

### Template API summary

| Method | Available in template? | Description |
|---|---|---|
| `with(array)` | no (builder) | Set data before rendering. |
| `layout(string)` | no (builder) | Set the layout for the next `render()`. |
| `render(string)` | no (builder) | Render a view to a string. |
| `start(string)` / `end()` | yes | Define a named section. |
| `section(string, string $default)` | yes | Echo a section's content. |
| `fragment(string, array)` | yes | Include a partial template. |
| `e(mixed)` | yes | Escape a value. |

### Security

`BaseView` resolves every template path against its base directory and rejects
any path that escapes it (no directory traversal). Missing templates throw a
clear `RuntimeException`.

---

## 17. The Bag (Data Container)

`Bag` is Pano's lightweight, immutable-by-default collection type. It behaves
like an array (it implements `ArrayAccess`, `IteratorAggregate`, `Countable`)
but adds functional helpers.

You have already met it as `$request->attributes`. You can also use it anywhere
you need a structured key/value container.

```php
use Pano\Foundation\Bag;

$bag = new Bag(['name' => 'Pano', 'tags' => ['php', 'web']]);
```

### Basic operations (from `BaseBag`)

```php
$bag->all(): array;
$bag->get('name', $default): mixed;
$bag->set('key', $value): static;
$bag->has('key'): bool;
$bag->remove('key'): static;
$bag->count(): int;
```

It is fully array-like:

```php
$bag['name'];        // get
$bag['name'] = 'X';  // set
isset($bag['name']); // has
unset($bag['name']); // remove
foreach ($bag as $k => $v) { ... }
count($bag);
```

### Functional helpers (from `Bag`)

Most helpers return a **new** `Bag` rather than mutating — they are chainable:

```php
$bag->merge($otherBagOrArray): static;     // union by key
$bag->replace($otherBagOrArray): static;   // array_replace semantics
$bag->only(['name', 'email']): static;     // keep only these keys
$bag->except(['password']): static;        // drop these keys
$bag->map(fn($v, $k) => strtoupper($v)): static;
$bag->filter(fn($v, $k) => $v !== null): static;
```

### Deep search

Bags can be nested (arrays and other Bags inside). Pano can find values or keys
anywhere in the tree and return their **dot-paths**:

```php
$bag->find('php');          // 'tags.0'   — first path to the value
$bag->findAll('php');       // ['tags.0'] — all paths
$bag->findKey('tags');      // 'tags'     — first path to the key
$bag->findAllKeys('tags');  // ['tags']   — all paths
```

Paths use `.` as the separator (e.g. `user.address.city`). This makes `Bag`
useful for configuration trees, JSON payloads, and nested request data.

---

## 18. Logging

Each module owns its own logger, writing to `Logs/` inside the module folder.
A new daily file (`log-YYYY-MM-DD.log`) is created automatically.

```php
$this->module->log()->info('User logged in', ['id' => 42]);
```

### Levels (RFC 5424)

```php
$log->emergency($msg, $context = []);
$log->alert($msg, $context = []);
$log->critical($msg, $context = []);
$log->error($msg, $context = []);
$log->warning($msg, $context = []);
$log->notice($msg, $context = []);
$log->info($msg, $context = []);
$log->debug($msg, $context = []);
```

### Format

Each line looks like:

```text
[14:22:05] info: User logged in {"id":42}
```

The `$context` array is JSON-encoded (with `JSON_UNESCAPED_UNICODE`). The log
directory is created if missing, and writes use `FILE_APPEND | LOCK_EX` for
safe concurrent appends.

### Using a different logger

`BaseLogger` is abstract; `Logger` is just the Foundation's file-based default.
If you want Monolog, Sentry, or a custom sink, implement `BaseLogger` and return
it from your module's `log()` method.

---

## 19. Exceptions & Error Handling

Pano converts every uncaught throwable into a response — you never need to
manually wrap handler code for output safety.

### The default exception

The Foundation ships `Pano\Foundation\Exception`, a `BaseException` subclass that
renders itself to JSON, HTML, or terminal output depending on the context:

```php
throw new \Pano\Foundation\Exception(
    'Post not found',
    code: 404,
    status: \Pano\Kernel\HttpStatusEnum::NOT_FOUND,
    payload: ['postId' => $id]
);
```

- `message` — the human message.
- `code` — the standard exception code.
- `status` — an `HttpStatusEnum` used for the HTTP response.
- `payload` — optional structured data attached to the rendered output.

### How it renders

`Response::exception($e, $request)` chooses the format:

| Context | Output |
|---|---|
| CLI request | colored terminal line, `ResultCodeEnum::ERROR` |
| `expectsJson()` request | `$e->toArray($debug)` as JSON |
| otherwise | `$e->toHtml($debug)` as HTML |

When `app.debug` is on, the rendered body includes the exception class and stack
trace; in production it is hidden.

### Custom exception types

For richer domain errors, extend `BaseException` and implement `toArray()` and
`toHtml()`:

```php
namespace Modules\Blog\Exceptions;

use Pano\Kernel\BaseException;
use Pano\Kernel\HttpStatusEnum;

final class ValidationException extends BaseException
{
    public function toArray(bool $debug = false): array
    {
        return [
            'message' => $this->getMessage(),
            'errors'  => $this->payload ?? [],
        ];
    }

    public function toHtml(bool $debug = false): string
    {
        return '<h1>Validation failed</h1><pre>'
             . htmlspecialchars($this->getMessage())
             . '</pre>';
    }
}
```

Throw it anywhere in a handler or interceptor — the global handler will render
it correctly.

### Non-`BaseException` throwables

Plain `\Throwable` instances (PHP errors, third-party exceptions) are rendered as
a generic `500 Server Error`, with the real message shown only in debug mode.

---

## 20. CLI Commands

Pano supports console commands through the same module/router system. The `pano`
executable is the CLI entry point. Reusing the HTTP pipeline means a command is
just a route resolved against a `CLIRequest` instead of an HTTP one.

### Invocation format

The CLI shares the module-resolution model with HTTP: the **first positional
argument is the module path**, the **second is the command**.

```bash
php pano <module-path> <command> [positional args...] [--options...]
```

The module path mirrors what you would see in a URL:

- For the **root** module (registered under the `''` key in `config/modules.php`):

  ```bash
  php pano / app:info
  ```

- For a **named** module (registered under e.g. `blog`):

  ```bash
  php pano blog blog:publish 42
  ```

Any further positional arguments after the command are passed to the handler as
the `$arguments` array; any `--key=value` (or `--flag`) arguments become options.

```bash
php pano / users:import users.csv --dry-run --batch=100
```

> On Windows / Git-Bash, paths beginning with `/` are mangled by the shell's
> path conversion. Prefix the command with `MSYS_NO_PATHCONV=1` (or quote the
> leading slash) when invoking from such shells:
>
> ```bash
> MSYS_NO_PATHCONV=1 php pano / app:info
> ```

### Registering a command

Inside a module's `routes()`, call `command()` with a command name and a
command class:

```php
$this->router->command('app:info', \Modules\Default\Commands\DefaultCommand::class);
```

The command name (`app:info`) is the string matched against the command segment
of the CLI request. It does **not** have to start with the module name — pick
any namespace-style name you like.

The command class must extend `BaseCommand` and implement `handle()`:

```php
namespace Modules\Blog\Commands;

use Pano\Kernel\BaseCommand;
use Pano\Kernel\ResultCodeEnum;

final class PublishCommand extends BaseCommand
{
    public function handle(array $arguments): ResultCodeEnum
    {
        // positional args are in $arguments (indexed array)
        $id = $arguments[0] ?? null;

        // --options are available on the request
        $dryRun = $this->request->getOptions()['dry-run'] ?? false;

        if ($id === null) {
            $this->error('Usage: blog:publish <id>');
            return ResultCodeEnum::INVALID;
        }

        $this->info("Published post {$id}" . ($dryRun ? ' (dry-run)' : ''));
        return ResultCodeEnum::OK;
    }
}
```

### Inside a command

`BaseCommand` gives you:

- `$this->request` — the `CLIRequest`, exposing:
    - `getPositional()` — indexed array of positional arguments (everything after
      the command that does not start with `--`),
    - `getOptions()` — associative array of `--key=value` / `--flag` options,
    - `getModule()`, `getCommand()`, `getSegments()`, `getPath()`.
- `$this->module` — the owning module (so `$this->module->log()` works in CLI too),
- `$this->info($text)` — print a green line,
- `$this->error($text)` — print a red line.

The `$arguments` array received by `handle()` is exactly `getPositional()`.

### Argument rules

The command name may declare optional segments just like HTTP routes, using the
bracket syntax. Required segments missing from the invocation throw
`"Parameter '<name>' is required"`.

```php
$this->router->command('blog:publish', PublishCommand::class);
$this->router->command('db:migrate [version?]', MigrateCommand::class);
```

### Return codes

`handle()` returns a `ResultCodeEnum`:

| Value | Meaning |
|---|---|
| `OK` | success |
| `ERROR` | general failure |
| `INVALID` | invalid input / usage error |

This drives the terminal output color and signals failure to the shell.

### Command output

For richer CLI responses, build a `Response::terminal($text, $status)` — it
prints colored output (green for `OK`, red for anything else) and respects the
result code.

---

## 21. Enums Reference

Pano uses strict enums throughout. Import them from `Pano\Kernel`.

### `HttpMethodEnum` (string-backed)

All standard HTTP methods plus WebDAV methods (`PROPFIND`, `MKCOL`, …), plus a
synthetic `CLI` value for console requests.

Helper methods:

```php
HttpMethodEnum::POST->allowsBody();     // bool — can carry a body
HttpMethodEnum::GET->isSafe();          // bool — RFC 7231 safe method
HttpMethodEnum::PUT->isIdempotent();    // bool
HttpMethodEnum::POST->isWrite();        // bool — typical write verb
HttpMethodEnum::fromString('post');     // case-insensitive construction
HttpMethodEnum::values();               // ['GET','POST', ...]
```

### `HttpStatusEnum` (int-backed)

The full HTTP status code set (1xx–5xx), e.g. `OK`, `CREATED`, `NOT_FOUND`,
`UNAUTHORIZED`, `UNPROCESSABLE_ENTITY`, `INTERNAL_SERVER_ERROR`.

Helper methods:

```php
HttpStatusEnum::OK->isSuccess();        // 2xx
HttpStatusEnum::NOT_FOUND->isClientError(); // 4xx
HttpStatusEnum::INTERNAL_SERVER_ERROR->isServerError(); // 5xx
HttpStatusEnum::NOT_FOUND->category();  // 4
```

### `ResultCodeEnum` (int-backed)

Used for CLI command exit semantics:

- `OK = 0`
- `ERROR = 1`
- `INVALID = 2`

### `LogLevelEnum` (string-backed)

RFC 5424 levels: `EMERGENCY`, `ALERT`, `CRITICAL`, `ERROR`, `WARNING`,
`NOTICE`, `INFO`, `DEBUG`.

---

## 22. Replacing the Foundation

The single most important idea in Pano: **the Kernel is fixed; the Foundation is
replaceable.** If the default HTTP runtime, response rendering, or template
engine does not suit you, you can swap it without forking Pano.

### What you may replace

Any Foundation class is fair game:

- `Request` / `CLIRequest` — custom request parsing (e.g. PSR-7 adapters).
- `Router` — a different dispatch strategy.
- `Response` — alternative output formats.
- `View` — a different template engine (Twig, Blade, plain PHP).
- `Logger` — Monolog, Sentry, syslog.
- `Bag` — a different collection implementation.
- `Exception` — your own error rendering.
- `Boot` — a fully custom bootstrap.

You do this by writing a class that extends the corresponding `Base*` Kernel
contract, then pointing your `index.php` / `pano` entry points and your modules
at your implementations.

### Example: a custom boot

```php
// public/index.php
require __DIR__ . '/../vendor/autoload.php';

(new \App\Foundation\Boot())->run($_SERVER);   // your boot, not Pano\Foundation\Boot
```

```php
namespace App\Foundation;

use Pano\Kernel\BaseBoot;

final class Boot extends BaseBoot
{
    public function __construct()
    {
        $this->envLoader();
        $this->debug(config('app.debug', false));
        // your own bootstrap steps here
    }

    public function run(array $data, bool $cli = false): void
    {
        // your own request handling, as long as it honors Kernel contracts
    }
}
```

### The contract you must honor

Whatever you replace, you must still satisfy the Kernel's abstract methods and
type signatures. That is the whole point: the Kernel guarantees the shape of
the system, you decide how it behaves inside that shape. This is how you can
build an entire custom framework *on top of* Pano.

---

## 23. Conventions & Best Practices

These follow directly from Pano's philosophy and will keep your codebase healthy.

### Do

- **Keep modules isolated.** A module should not reach into another module's
  internals. Talk to other modules through explicit classes/contracts.
- **Reference handlers and interceptors by class name** (`PostHandler::class`),
  never by magic strings. This makes routes refactor-safe and IDE-friendly.
- **Always declare return types**, and always return a `Response` from handlers.
  The router enforces this — don't fight it.
- **Escape output** with `$this->e(...)` in every template.
- **Use `$request->attributes`** to pass data from interceptors to handlers
  (the authenticated user, request ID, etc.).
- **Throw `BaseException` subclasses** for expected domain errors so they render
  cleanly in any format.
- **Prefer readability over brevity.** Pano values traceable, explicit code.
- **Respect the layer boundaries** — Kernel defines, Foundation implements,
  Modules consume. Don't put application logic in the Foundation.

### Don't

- **Don't introduce hidden global state.** No singletons, no service locators,
  no `static` caches that other code reads implicitly.
- **Don't bypass the router.** Always go through `$this->router->...`.
- **Don't echo from handlers.** Build a `Response` and return it.
- **Don't add third-party dependencies to the Kernel** — that breaks the
  zero-dependency contract.
- **Don't nest template sections.** Close one before starting another.
- **Don't suppress exceptions silently.** Let them propagate; Pano renders them.

---

## 24. Full Example — Building a Module

This end-to-end example ties everything together: a tiny `blog` module with a
list route, a detail route, an auth interceptor, a JSON endpoint, a view, and a
CLI command.

### 1. Register the module

```php
// config/modules.php
return [
    ''    => \Modules\Default\DefaultModule::class,
    'blog' => \Modules\Blog\BlogModule::class,
];
```

### 2. The module class

```php
namespace Modules\Blog;

use Pano\Foundation\Logger;
use Pano\Foundation\View;
use Pano\Kernel\BaseLogger;
use Pano\Kernel\BaseModule;
use Pano\Kernel\BaseRouter;
use Pano\Kernel\BaseView;
use Modules\Blog\Commands\PublishCommand;
use Modules\Blog\Handlers\PostHandler;
use Modules\Blog\Interceptors\AuthInterceptor;

final readonly class BlogModule extends BaseModule
{
    public function routes(): BaseRouter
    {
        $this->router->get('/', PostHandler::class, 'index');
        $this->router->get('/posts/[id]', PostHandler::class, 'show');
        $this->router->post('/posts', PostHandler::class, 'store', [AuthInterceptor::class]);
        $this->router->command('blog:publish', PublishCommand::class);

        return $this->router;
    }

    public function view(): BaseView
    {
        return new View($this->viewPath());
    }

    public function log(): BaseLogger
    {
        return new Logger($this->logPath());
    }
}
```

### 3. The interceptor

```php
namespace Modules\Blog\Interceptors;

use Pano\Foundation\Exception;
use Pano\Kernel\BaseInterceptor;
use Pano\Kernel\BaseResponse;
use Pano\Kernel\HttpStatusEnum;

final class AuthInterceptor extends BaseInterceptor
{
    public function onRequest(): void
    {
        $token = $this->request->getHeaders()['authorization'] ?? '';

        $userId = $this->verify($token);   // your own logic

        if ($userId === null) {
            throw new Exception(
                'Unauthorized',
                status: HttpStatusEnum::UNAUTHORIZED
            );
        }

        $this->request->attributes->set('userId', $userId);
    }

    public function onResponse(BaseResponse $response): BaseResponse
    {
        return $response->setHeader('X-Blog-Version', '1.0');
    }

    private function verify(string $token): ?int { /* ... */ return 1; }
}
```

### 4. The handler

```php
namespace Modules\Blog\Handlers;

use Pano\Foundation\Response;
use Pano\Kernel\BaseHandler;
use Pano\Kernel\HttpStatusEnum;

final class PostHandler extends BaseHandler
{
    public function index(): Response
    {
        return Response::json(['posts' => $this->allPosts()]);
    }

    public function show(string $id): Response
    {
        $post = $this->findPost($id);

        if ($post === null) {
            throw new \Pano\Foundation\Exception(
                'Post not found',
                status: HttpStatusEnum::NOT_FOUND,
                payload: ['id' => $id]
            );
        }

        return Response::html(
            $this->module->view()
                ->with(['post' => $post])
                ->layout('layout')
                ->render('show')
        );
    }

    public function store(): Response
    {
        $userId = $this->request->attributes->get('userId');
        $data   = $this->request->getData();

        $this->module->log()->info('Post created', ['by' => $userId]);

        return Response::json(['status' => 'created'], HttpStatusEnum::CREATED);
    }

    private function allPosts(): array { return [['id' => 1, 'title' => 'Hello']]; }
    private function findPost(string $id): ?array { return ['id' => $id, 'title' => 'Hello']; }
}
```

### 5. The view (`modules/Blog/Views/show.php`)

```php
<?php $this->start('title') ?><?= $this->e($post['title']) ?><?php $this->end() ?>

<article>
    <h1><?= $this->e($post['title']) ?></h1>
    <p>Post ID: <?= $this->e($post['id']) ?></p>
</article>
```

### 6. The CLI command

```php
namespace Modules\Blog\Commands;

use Pano\Kernel\BaseCommand;
use Pano\Kernel\ResultCodeEnum;

final class PublishCommand extends BaseCommand
{
    public function handle(array $arguments): ResultCodeEnum
    {
        $id = $arguments[0] ?? null;

        if ($id === null) {
            $this->error('Usage: blog:publish <id>');
            return ResultCodeEnum::INVALID;
        }

        $this->info("Published post {$id}");
        return ResultCodeEnum::OK;
    }
}
```

### 7. Run it

```bash
# Web
php -S localhost:8000 -t public
#   http://localhost:8000/blog           → index (JSON)
#   http://localhost:8000/blog/posts/1   → show (HTML)

# Console (blog module is registered under the 'blog' key)
php pano blog blog:publish 1
```

---

## Appendix — Class Map

### Kernel contracts (`Pano\Kernel`) — abstract, do not instantiate

| Class | Role |
|---|---|
| `BaseBoot` | Bootstrap contract: env loading, debug, dispatch. |
| `BaseModule` | Module contract: routes, view, log, paths. |
| `BaseRouter` | Routing engine: registration, compilation, dispatch. |
| `BaseRequest` | Request shape: method, url, headers, body, files, attributes. |
| `BaseResponse` | Response shape: status, headers, body, `send()`. |
| `BaseHandler` | Handler base: receives request + module. |
| `BaseInterceptor` | Interceptor base: `onRequest()` / `onResponse()`. |
| `BaseView` | Template engine: render, layout, sections, fragments, escaping. |
| `BaseLogger` | Logger contract: 8 RFC-5424 levels. |
| `BaseBag` | Array-like collection with `ArrayAccess`/`IteratorAggregate`/`Countable`. |
| `BaseCommand` | CLI command base: `handle(array): ResultCodeEnum`. |
| `BaseException` | Exception base: `toArray()` / `toHtml()` rendering. |

### Foundation implementations (`Pano\Foundation`) — replaceable

| Class | Role |
|---|---|
| `Boot` | Default bootstrap (`run(array $data, bool $cli = false)`). |
| `Router` | Default router (GET/POST/PUT/DELETE + commands). |
| `Request` | HTTP request built from `$_SERVER`. |
| `CLIRequest` | Console request built from `$argv`. |
| `Response` | Response factory: `json`, `text`, `html`, `stream`, `redirect`, `terminal`. |
| `View` | Default template engine. |
| `Logger` | File-based daily logger. |
| `Bag` | Collection with merge/replace/only/except/map/filter/find/search. |
| `Exception` | Default renderable exception. |

### Enums (`Pano\Kernel`)

`HttpMethodEnum`, `HttpStatusEnum`, `ResultCodeEnum`, `LogLevelEnum`.

---

*Pano provides the execution structure. The architectural decisions are yours.*
