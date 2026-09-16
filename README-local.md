# README - Local

---

now lets build and document `docs/poc-2 gcp-sse-chatbot/1-1-idea-specs.md`:

- `## TODO - Domain 7: Production Hardening (SSE pitfalls)`

---

## Pre-prompt (paste at top of every new chat)

---

> **VERY IMPORTANT — read and follow these instructions in order:**

### 1. Load project context

Read these files first — they contain the project's conventions, architecture, and coding standards:

```txt
CLAUDE.md
```

### 2. Check project specs

Use the `Laravel Boost :: application-info` MCP tool to verify the current package versions and PHP version.

### 3. Activate relevant skills

This project has domain-specific skills in `.claude/skills/` (e.g., `laravel-best-practices`, `livewire-development`, `pest-testing`, `tailwindcss-development`). Activate the relevant skill(s) before working in that domain.

For every Pest test task, always activate both of these skills:

```txt
.claude/skills/testing-best-practices
.claude/skills/pest-testing-boundaries
```

For any Livewire / Alpine / raw JS / client-side animation / debugging work
(events, payload shapes, custom animations, typing indicators, auto-scroll,
console.log debugging), first read and follow:

```txt
.claude/skills/livewire-alpine-js-debugging
```

Before touching any Spatie Data DTO (`app/Data`), a Livewire component that
passes data into Blade, an array-shape PHPStan docblock (`array{...}`), or a
`toArray()` override, first read and follow:

```txt
.claude/skills/spatie-data-dto-livewire-boundary
```

It explains the verified Livewire-boundary rule (DTOs flatten to arrays), why
DTOs need the explicit `toArray()` override for PHPStan, and how the array keys
must stay aligned through `DTO → #[Computed] → child component → Blade`.

### 4. Follow existing code style

Before creating or editing a file, check **sibling files** and **related code** for the current patterns:

- Creating a test? Look at existing tests in the same or neighboring related directory.
- Creating a service? Check other services for the same patterns.
- Creating a Vue component? Check existing components for conventions.

### 5. Core rules

- **No overengineering** — keep it clean, simple, and consistent with the existing codebase.
- **No new dependencies** without explicit approval.
- **Prefer `php artisan make:*`** for scaffolding when it fits project conventions.
- **Check for existing components** before writing new ones.
- **Use `ddev` prefix** for PHP/composer/artisan commands (`ddev artisan`, `ddev composer`, `ddev pest`), but not for frontend/local NPM commands (`npm run`)

### 6. After completing the job

Run these in order and fix any errors:

```bash
# Backend changes (Pint + Rector + TypeScript + PHPStan + Pest)
ddev composer format-basic

# Frontend + DTO changes (builds assets, catches Vite/TypeScript errors)
npm run build
```

### 7. Tests

- Update tests when the codebase changes — but first verify the code change is correct.
- No need for backward compatibility for most changes (or otherwise stated).
- Run affected tests to confirm they pass and if not fix the errors.

### 8. AI CODING AGENT OPERATIONS & CODEBASE UNDERSTANDING RULES

#### 0. Read > Plan > Patch > Verify > Review (add tests?)

#### 1. Graph-Based Codebase Navigation

- Do not treat this codebase as flat text. You must traverse it as a dependency graph of logical references, function calls, class hierarchies, and modules.
- When an execution path is unclear, explicitly trace the import trees and data models end-to-end before proposing structural changes.

#### 2. Dynamic Structural Scanning

- Before modifying or generating code, partition your analysis into distinct, meaningful semantic chunks (classes, methods, interfaces) instead of reading random text blocks.
- Map the explicit abstractions and relationships of the affected domain to prevent regressions in tightly coupled modules.

#### 3. Strict Context Optimization (Node-First Retrieval)

- Prevent information overload and context-window pollution. Fetch and analyze repository files iteratively and targeted ("just-in-time"), focusing only on the specific execution nodes relevant to the current task.
- Rely on verified local or cloud-based indexing structures to pinpoint code logic, rather than guessing file relevance via blanket keyword searches.

#### 4. Multi-Step Execution Planning

- You must function with goal-oriented reasoning: formulate a declarative, multi-step execution plan (identifying dependencies, required refactors, and external tools) before writing a single line of code.
- Anticipate the ripple effects of your edits downstream in the program's structural well-formedness.

#### 5. Reflection & Test-Driven Verification

- For every code change, execute an internal self-correction and reflection loop.
- Validate your output against the repository's compilers, linters, and test suites. If a regression or test failure occurs, adjust your implementation path autonomously based on the error output.

---

---

> **The job to be done:**
