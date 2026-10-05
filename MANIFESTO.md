# Pano Manifesto

This document explains why Pano exists, what direction it follows and the philosophy behind it.

This is not technical documentation.

This is the spirit of Pano.

---

# Why Pano Exists

Sometimes all you need is a tiny application running on a small host.

Maybe:

- a simple résumé page
- a contact form
- or a slightly more complex website

In those situations, using a massive framework makes little sense.

---

Sometimes the problem is the opposite.

A developer wants to build a system around a specific architectural vision, but most frameworks already enforce their own architectural decisions.

As a result, developers are forced to:

- sacrifice their architectural vision
- fight against the framework
- or settle for the closest available option

---

Sometimes developers decide to build their own framework.

But building a framework from scratch is:

- time-consuming
- exhausting
- and full of repeated low-level problems

Pano was created to solve these issues.

---

# What Is Pano?

Pano is a lightweight and minimal nano-framework for PHP.

Pano does not try to be everything.

Pano tries to be a foundation upon which anything can be built.

Pano is closer to a Kernel than a complete framework.

Much like the Linux kernel, it only provides the Kernel execution structure while everything else can be built on top of it.

---

# Freedom Is The Kernel Principle

Pano was not created to impose architecture.

The goal of Pano is to allow developers to:

- implement any architecture they want
- build any kind of software
- and even create their own framework on top of it

Pano should never make architectural decisions on behalf of developers.

---

# Pano Is Not A Decision-Making Framework

Most frameworks make decisions for developers:

- how projects should be structured
- how dependencies should work
- how execution should behave
- how components should communicate

Pano deliberately avoids this approach.

In Pano:

- control belongs to the developer
- responsibility belongs to the developer
- and architectural direction remains open

This is why Pano is not designed for inexperienced teams building large systems.

Pano is designed for developers who:

- know where they are
- know where they want to go
- and are capable of making architectural decisions

---

# Minimalism Is A Deliberate Choice

Pano is intentionally minimal.

This means:

- no external dependencies
- maximum independence
- and intentionally unimplemented features

Pano is not trying to solve every problem.

Pano is not trying to provide built-in solutions for everything.

If a good solution already exists, developers are free to use it themselves.

---

# Why There Are No External Dependencies

Fewer dependencies mean:

- more control
- more predictable behavior
- less complexity
- greater independence
- deeper customization possibilities

Pano tries to remain as self-contained as possible.

This is why many features commonly implemented through third-party packages simply do not exist in Pano.

---

# What Pano Actually Implements

Pano only focuses on foundational responsibilities.

Such as:

- request lifecycle
- request processing
- response handling
- rendering multiple response types
- a minimal template engine
- execution management
- modular composition (modules and packages)

Pano does not try to replace every tool.

---

# A Strict Kernel With A Replaceable Foundation

The philosophy of Pano is built around a strict abstract Kernel.

Following this Kernel is mandatory.

However, the Kernel itself is intentionally tiny and minimal.

On top of this Kernel sits an execution foundation that is entirely replaceable.

The Foundation is where concrete runtime choices live: how modules are resolved,
which classes implement request, response, router, view, and logger, and how the
process boots. Developers may replace the entire Foundation while still honoring
the Kernel contracts.

This allows developers to:

- build their own foundation
- define their own module registry and resolution strategy
- create their own framework
- and then build applications on top of that foundation

Pano only opens the path.

Developers decide what that path becomes.

---

# The Suggested Architecture

Pano tries to remain architecture-independent.

However, every framework eventually requires at least a minimal execution structure.

The suggested architecture in Pano is modular architecture.

In this model:

- each module is isolated
- each module is independently developed
- each module operates on top of the foundation
- packages may extend a module without becoming independent applications

But this architecture is not absolute.

It is only the default architectural direction of Pano itself.

---

# Concepts Created By This Philosophy

Pano's architecture-independent philosophy led to several concepts that are less common in traditional frameworks.

Such as:

- Handlers
- Interceptors
- Bag
- Packages

These concepts do not exist to enforce architecture.

They exist to provide flexibility.

---

# Stability Matters More Than Fast Adoption

Pano intentionally adopts new PHP versions slowly.

If PHP reaches version 10, Pano will only migrate to version 9.

Pano will always remain one version behind.

The reason is simple:

Someone may have built an entire framework on top of Pano.

Because Pano is foundational software, updates must be introduced carefully.

---

# Release Philosophy

Pano does not release updates aggressively.

- major versions are released yearly
- security updates have no schedule limitation
- performance improvements are released more conservatively

For Pano, stability matters more than rapid feature delivery.

---

# Readability Matters More Than Short Syntax

In Pano, code readability is extremely important.

Code should be:

- understandable
- traceable
- behaviorally clear

Pano does not encourage excessive syntax compression.

This does not mean code should become unnecessarily verbose.

However, because many systems are implemented from the ground up, some complexity is unavoidable.

---

# Strong Typing And Object-Oriented Design

Pano is heavily built around object-oriented design and strict typing.

Because of this:

- function inputs and outputs matter
- contracts must remain explicit
- object-oriented structure must be respected

This is why PHP 8 was selected as the starting version.

And according to Pano's philosophy, it will remain on that version until PHP 10 is released.

---

# What Pano Wants To Be

Pano does not want to become the biggest PHP framework.

It does not want to become the most feature-rich framework.

It does not want to satisfy everyone.

Pano wants to be:

- foundational
- independent
- controllable
- flexible

You are free. Pano should never think or make decisions on behalf of developers.
