# Pano

**Pano** is a lightweight PHP nano-framework designed as a minimal execution foundation.

It provides a small, explicit runtime that allows developers to build any architecture on top of it.

---

## What Pano Is

- a minimal execution foundation
- a low-level runtime structure
- a base for building custom frameworks
- a modular PHP system
- a pure library (no application scaffolding included)

---

## What Pano Is NOT

- a full-stack framework
- an opinionated application framework
- a batteries-included ecosystem
- a replacement for Laravel or similar frameworks
- a ready-to-run application skeleton

---

## When to Use Pano

Use Pano when you need:

- full control over application architecture
- a custom framework design
- a minimal runtime without imposed structure
- a base layer for system-level design

---

## When NOT to Use Pano

Avoid Pano if you need:

- rapid application scaffolding
- prebuilt authentication systems
- ORM-driven development
- convention-based frameworks
- beginner-friendly structure

> For a ready-to-run application skeleton, use **[pano-php/pano](https://github.com/pano-php/pano)** instead.

---

## Key Concepts

Pano introduces a minimal set of runtime concepts:

- **Kernel** – abstract contracts only (`Pano\Kernel\*`)
- **Foundation** – default concrete implementations (`Pano\Foundation\*`) – replaceable
- **Modules** – isolated application domains
- **Handlers** – executable processing units
- **Interceptors** – request/response pipeline

These concepts are intentionally low-level and composable.

---

## Requirements

- PHP >= 8.2
- Composer

---

## Installation

```bash
composer require pano-php/pano-framework
```

---

## Quick Start (Library Usage)

Since Pano is now a pure library, you must bootstrap it yourself (or use the official skeleton).

Minimal bootstrap example:

```php
<?php
// public/index.php (or your entry point)

define('PANO_STARTED', microtime(true));
define('BASE_PATH', rtrim(__DIR__ . '/../', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

require BASE_PATH . 'vendor/autoload.php';

(new \Pano\Foundation\Boot())->run($_SERVER);          // Web
// (new \Pano\Foundation\Boot())->run($argv, true);    // CLI
```

---

## Project Structure (Recommended via Skeleton)

Use the official skeleton for a complete working layout:

```bash
composer create-project pano-php/pano my-app
```

```text
my-app/
├── pano                      # CLI entry point
├── public/
│   └── index.php             # Web front controller
├── config/
│   ├── app.php
│   └── modules.php
├── modules/                  # Your application modules
│   └── Default/
│       ├── DefaultModule.php
│       ├── Handlers/
│       ├── Interceptors/
│       ├── Commands/
│       └── Views/
├── .env
└── composer.json
```

---

## Minimum Required Configuration

### `config/app.php`

This file is **required**. The framework reads it through the `config()` helper.
At minimum it must contain the following keys:

```php
<?php

return [
    'name'     => env('APP_NAME', 'Pano'),          // Application name
    'env'      => env('APP_ENV', 'local'),          // Environment: local | production | ...
    'key'      => env('APP_KEY', null),             // Application key (used for encryption/signing if needed)
    'debug'    => env('APP_DEBUG', false),          // Show detailed errors (true in development)
    'url'      => env('APP_URL', null),             // Base URL of the application (used by url() helper and subdomain resolver)
    'resolver' => env('MODULE_RESOLVER', 'path'),   // Module resolver: "path" or "subdomain"
    'timezone' => env('APP_TIMEZONE', 'UTC'),       // Default timezone (optional but recommended)
];
```

| Key        | Type         | Default  | Description                                      |
|------------|--------------|----------|--------------------------------------------------|
| `name`     | string       | `Pano`   | Application display name                         |
| `env`      | string       | `local`  | Current environment                              |
| `key`      | string\|null | `null`   | Application secret key                           |
| `debug`    | bool         | `false`  | Enable/disable detailed error display            |
| `url`      | string\|null | `null`   | Base application URL                             |
| `resolver` | string       | `path`   | How modules are resolved (`path` or `subdomain`) |
| `timezone` | string       | `UTC`    | Default timezone for the application             |

### Corresponding `.env` example

```dotenv
APP_NAME=Pano
APP_ENV=local
APP_KEY=base64:your-generated-key-here
APP_DEBUG=true
APP_URL=https://example.com
MODULE_RESOLVER=path
APP_TIMEZONE=UTC
```

---

## Documentation

- [MANIFESTO.md](MANIFESTO.md) → philosophy and principles
- [ARCHITECTURE.md](ARCHITECTURE.md) → system design and runtime model
- [DOCUMENTATION.md](DOCUMENTATION.md) → complete developer guide and API reference

---

## Version Compatibility

- PHP 8.2+ is the baseline runtime
- Pano evolves conservatively to maintain stability

---

## Contribution

Before contributing:

- read `MANIFESTO.md` and `ARCHITECTURE.md` then `DOCUMENTATION.md`
- respect Kernel boundaries
- avoid introducing hidden behavior
- keep runtime explicit and predictable

---

## Status

Pano is a low-level framework foundation intended for advanced use cases.

**You are free.** Pano should never think or make decisions on behalf of developers.

---

## License

MIT
