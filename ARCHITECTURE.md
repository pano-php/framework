# Pano Architecture

This document defines the technical architecture of Pano.

It explains:

- system structure
- runtime model
- execution flow
- architectural boundaries
- extension points
- runtime contracts
- and internal responsibilities

This document does not explain the philosophy behind Pano.

For architectural vision and project philosophy, refer to `MANIFESTO.md`.

---

# System Model

Pano is built around three primary layers:

```text
Kernel
    ↓
Foundation
    ↓
Modules
```

Each layer has a specific responsibility and must remain isolated from unrelated concerns.

---

# Kernel

The Kernel is the mandatory and lowest-level layer of Pano.

The Kernel defines:

- execution contracts
- runtime abstractions
- internal execution rules
- shared execution structures

The Kernel must remain:

- minimal
- deterministic
- dependency-free
- architecture-neutral

The Kernel is not responsible for:

- application architecture
- business logic
- domain structure
- application conventions

---

# Kernel Responsibilities

The Kernel is responsible for defining the minimum executable structure of the system.

Including:

- execution contracts
- handler contracts
- interceptor contracts
- response contracts
- runtime context contracts
- execution boundaries
- package contracts (`BasePackage`)
- foundation contracts (`BaseFoundation`) for swappable runtime class resolution and module registry

The Kernel should never contain application-specific behavior.

---

# Foundation

The Foundation is the default runtime implementation layer of Pano.

It surrounds the Kernel and provides executable behavior.

The Foundation is responsible for:

- request lifecycle management
- execution orchestration
- response resolution
- template rendering
- runtime coordination
- default execution flow
- module class resolution via a static registry (`BaseFoundation::$modules` / `module()` / `modules()`)
- per-module resolution via `ModuleResolverEnum` and `resolver()` / `param()`
- bootstrap loading of `.env` and `config/*.php` (concrete Boot: `envLoader` / `configLoader`)
- module-aware URL generation (`url($path, $moduleParam)`)

The constant `FOUNDATION` is defined at boot time and points to the active foundation instance, allowing Kernel contracts and modules to resolve concrete classes without hard-coding the default Foundation.

---

# Replaceable Foundation

The Foundation is intentionally replaceable.

Developers may:

- replace the entire Foundation
- create custom runtime behavior
- define alternative execution flows
- build their own framework on top of the Kernel
- override module registry entries and per-key `ModuleResolverEnum` strategy by extending `BaseFoundation`

The Kernel remains stable while Foundations may vary.

---

# Modules

Pano is designed to support modular development effectively.

Each module should remain:

- isolated
- independently maintainable
- independently understandable
- explicitly bounded

Modules declare their routes, interceptors and packages inside the `setup()` method.

Modules may import packages (`importPackages()`) before calling `setup()`. Packages are specialized modules that extend `BasePackage` and cannot themselves import further packages.

How a module key is selected for an incoming request is controlled per entry by
`ModuleResolverEnum` (`PATH`, `SUBDOMAIN`, `HOST`, `QUERY`, `HEADER`) on the Foundation registry.

Modules expose a `path()` helper that resolves filesystem paths relative to the module class location (Views, Files, Logs, etc.).

Modules should communicate through explicit contracts rather than implicit shared state.

---

# Default Project Structure

```text
project/
│
├── config
├── src
│   ├── Kernel
│   ├── Enum
│   ├── Foundation
│   └── Modules
```

---

# Directory Responsibilities

## config

Contains runtime configuration and environment configuration.

---

## Kernel

Contains execution contracts and runtime abstractions (`Base*` classes, including `BaseFoundation`, `BaseModule`, `BasePackage`, `BaseRouter`, …).

---

## Enum

Contains shared enumerations and constant-driven structures.

---

## Foundation

Contains the default runtime implementation layer.

This layer is replaceable.

---

## Modules

Contains application modules and domain logic.

---

# Runtime Lifecycle

The default runtime lifecycle is intentionally explicit.

```text
Bootstrap (BASE_PATH, envLoader, configLoader, FOUNDATION)
    ↓
Runtime Initialization (debug, timezone)
    ↓
Request Resolution
    ↓
Module Resolution (ModuleResolverEnum → Foundation::module)
    ↓
Router + Package Import
    ↓
Module setup()
    ↓
Interceptor Pipeline
    ↓
Handler Execution
    ↓
Response Resolution
    ↓
Response Dispatch
    ↓
Termination (CLI exit code / process exit)
```

Execution flow should remain understandable and observable.

---

# Interceptors

Interceptors provide execution interception capabilities.

Interceptors may:

- transform requests
- validate execution state
- perform authorization
- observe runtime behavior
- alter execution flow

Routers support default interceptors and route grouping with shared prefixes and interceptors (`grouping()`).

Interceptors should remain transport-independent.

---

# Handlers

Handlers are executable processing units.

Handlers are responsible for executing a single operation.

Handlers should remain:

- deterministic
- isolated
- explicitly scoped

Handlers should not contain hidden runtime side effects.

---

# Runtime Context (Request)

The Runtime Context represents the current execution state.

It may contain:

- request information
- execution metadata
- runtime state
- shared execution references

Runtime Contexts should remain explicit and traceable.

---

# Response Resolution

Pano separates execution from response rendering.

Handlers return executable results.

The response resolver transforms execution results into renderable responses.

This separation allows runtime flexibility across multiple transport layers.

---

# Extension Model

Pano supports extension primarily through:

- Foundation replacement (`BaseFoundation` + concrete Foundation)
- module composition
- packages (`BasePackage` extending `BaseModule`)
- interceptor pipelines (including group-level and default interceptors)
- runtime contracts
- route grouping with shared prefixes and interceptors

Extensions should preserve execution predictability.

---

# Architectural Constraints

Pano intentionally restricts:

- hidden execution behavior
- implicit runtime mutation
- global mutable state
- framework-driven architecture enforcement
- magic-based execution
- hidden dependency resolution

Execution behavior must remain explicit.

---

# Runtime Invariants

The following rules should always remain true:

- execution must remain deterministic
- modules must remain isolated
- foundations must not violate Kernel contracts
- execution flow must remain observable
- runtime mutation must remain explicit
- architectural assumptions must not become mandatory
- packages cannot import other packages

---

# Code Design Principles

Pano prioritizes:

- readability
- explicit contracts
- strong typing
- object-oriented design
- predictable behavior

Clarity is preferred over compact syntax.

---

# Final Principle

The framework provides execution structure.

Architectural decisions belong to developers.
