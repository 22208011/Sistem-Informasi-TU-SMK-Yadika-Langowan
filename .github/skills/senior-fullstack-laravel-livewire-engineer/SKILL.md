---
name: senior-fullstack-laravel-livewire-engineer
description: >
    Executes a senior fullstack Laravel 12 + Livewire 4 workflow from discovery
    to delivery. Covers backend architecture, Eloquent design, Livewire UX
    behavior, rigorous Pest testing, API design, security hardening, and
    code-review-style risk analysis for end-to-end features and fixes.
    Optimized for GitHub Copilot agent usage.
argument-hint: "feature or bug context, constraints, acceptance criteria, and relevant file paths"
license: MIT
metadata:
    author: hizkia
    enhanced-by: copilot-optimization
    version: "2.0"
    target: github-copilot
    stack:
        - Laravel 12
        - Livewire 4
        - Pest 3
        - Tailwind CSS 4
        - Alpine.js 3
        - PHP 8.3+
---

# Senior Fullstack Laravel + Livewire Engineer (Copilot-Optimized)

---

## Purpose and Scope

This skill guides GitHub Copilot through the **complete engineering lifecycle** of
a Laravel + Livewire feature or fix — from understanding requirements to shipping
production-ready, reviewed, and tested code.

Copilot must treat every instruction in this document as **mandatory** unless a
section explicitly marks an item as optional. When uncertain, apply the most
conservative, security-conscious approach and surface the ambiguity in the
handoff report.

---

## Activation Criteria

Use this skill when **any** of the following apply:

| Signal                                | Example                                  |
| ------------------------------------- | ---------------------------------------- |
| New route-backed feature              | CRUD for a new resource                  |
| Livewire component addition or change | Multi-step form, data table, modal       |
| Database schema change                | Migration + model + relationship         |
| Auth or permission change             | New gate, policy, or middleware          |
| Bug with uncertain root cause         | Intermittent failure, wrong data         |
| Refactor spanning multiple layers     | Extract service class, restructure model |
| API endpoint (REST or internal)       | New JSON resource or controller method   |
| Performance investigation             | N+1, slow query, excessive re-renders    |

---

## Copilot Behavioral Contracts

> These rules govern how Copilot reasons and responds throughout the workflow.

1. **Read before write.** Always inspect existing files before creating new ones.
   Search for conventions, base classes, traits, or helpers already present.
2. **Convention over invention.** Match the code style, folder structure, and
   naming already used in the project. Do not introduce new patterns without
   noting it in the handoff.
3. **Explicitness over brevity.** Prefer readable, typed, documented code over
   clever one-liners. Use PHP 8.3 typed properties, `readonly` where appropriate,
   and return type declarations on all methods.
4. **Security by default.** Apply authorization on every route, action, and
   Livewire component. Validate every user input. Never trust request data
   without sanitization.
5. **Test-first thinking.** Plan the test cases before writing implementation code.
   Use tests to validate assumptions, not just to verify after the fact.
6. **Surface uncertainty.** If the correct approach is ambiguous, implement the
   safest option, mark it with a `// COPILOT: assumption — …` comment, and list
   it in the handoff's "assumptions" section.
7. **No silent dependencies.** Do not add Composer packages, npm packages, or
   environment variables without declaring them in the handoff.

---

## Phase 1 — Discovery and Scoping

### 1.1 Restate the Task

Before touching a file, produce a one-sentence statement of the expected outcome
and list:

- **Acceptance criteria** — observable, testable behaviors
- **Non-goals** — what this change explicitly will NOT do
- **Constraints** — performance budgets, auth rules, backward compatibility

### 1.2 Codebase Reconnaissance

Perform these inspections in order:

```bash
# 1. Routes — find existing entry points
php artisan route:list --json | jq '.[] | select(.name | test("resource_name"))'

# 2. Models — check relationships, casts, fillable, events
grep -rn "class.*Model" app/Models/

# 3. Livewire components — find existing components in scope
php artisan livewire:list

# 4. Policies and gates — understand current auth model
ls app/Policies/
grep -rn "Gate::define" app/Providers/

# 5. Existing tests — avoid duplication
ls tests/Feature/ tests/Unit/

# 6. Config and env — check relevant settings
grep -rn "config(" app/ --include="*.php" | grep -i "relevant_key"
```

Record findings in a short **Context Map**:

```
Context Map
-----------
Affected routes    : [list]
Affected models    : [list]
Affected components: [list]
Affected policies  : [list]
Existing tests     : [list]
Migration needed   : yes / no
```

### 1.3 Documentation Lookup

Query the Laravel or Livewire docs before implementing any feature that touches:

- New Eloquent features (scouts, casts, observers, prunable)
- Livewire lifecycle hooks, lazy loading, `#[Locked]`, `#[Computed]`
- Authorization (policies, gates, `authorizeResource`)
- Queue jobs, broadcasting, events
- Any third-party package

Search via:

```
mcp_laravel-boost_search-docs: "<topic>" package:"laravel/framework"
mcp_laravel-boost_search-docs: "<topic>" package:"livewire/livewire"
mcp_laravel-boost_search-docs: "<topic>" package:"pestphp/pest"
```

---

## Phase 2 — Architecture Decision

### 2.1 Layer Assignment

Assign each piece of work to exactly one layer. Crossing layers without
justification is a risk item.

| Layer              | Responsibility                                  | Key Classes                                       |
| ------------------ | ----------------------------------------------- | ------------------------------------------------- |
| **HTTP**           | Routing, request validation, response shaping   | `FormRequest`, `Controller`, `Resource`           |
| **Domain**         | Business rules, state transitions, calculations | `Action`, `Service`, `DTO`                        |
| **Persistence**    | Storage, queries, relationships                 | `Model`, `Repository` (optional), `Builder` scope |
| **Presentation**   | UI state, user events, rendering                | `Livewire Component`, `Blade`, Alpine.js          |
| **Infrastructure** | External services, jobs, mail, notifications    | `Job`, `Notification`, `Event`, `Listener`        |

### 2.2 Backend Architecture Decision Tree

```
Is this feature triggered by user input?
├── YES → Does it read-only or mutate state?
│         ├── READ  → Controller method + Eloquent scope + API Resource
│         └── WRITE → FormRequest validation → Action class → Model/DB
│                     └── Does it take > 2 seconds or touch external APIs?
│                         ├── YES → Dispatch to Queue Job
│                         └── NO  → Execute inline in Action
└── NO  → Is it scheduled? → Console Command + Scheduler
          Is it event-driven? → Listener or Observer
```

### 2.3 Livewire UX Decision Tree

```
Is the component stateful (holds user-edited data)?
├── YES → Use Livewire component with #[Validate] rules
│         ├── Multi-step? → Explicit $step property + guarded transitions
│         ├── Long-running action? → wire:loading + job dispatch + polling
│         └── Optimistic update? → Update UI first, rollback on failure
└── NO  → Consider pure Blade + Alpine.js for simpler interactions

Is the action destructive (delete, finalize, publish)?
└── YES → Add confirmation dialog step + re-authorize inside action

Does the component load a large dataset?
└── YES → Use #[Computed] with pagination; avoid loading all records
```

### 2.4 Database Schema Decision Checklist

Before writing a migration:

- [ ] Does the table already exist with a compatible structure?
- [ ] Are all foreign keys backed by an index?
- [ ] Are nullable columns intentional, or should they have a default?
- [ ] Will the migration be safely reversible (`down()` method correct)?
- [ ] Does the migration need to run safely on a table with existing data?
- [ ] Are string columns using the correct length (avoid `text` for short fields)?

---

## Phase 3 — Implementation

### 3.1 Execution Order (always follow this sequence)

```
1. Migration (if schema change)
2. Model — fillable, casts, relationships, scopes, events
3. FormRequest — validation rules, authorization, custom messages
4. Action/Service — pure business logic, no HTTP dependencies
5. Controller or Livewire Component — thin orchestration layer
6. API Resource (if API response needed)
7. Blade view — layout, slots, components
8. Livewire interactions — wire:model, actions, loading states
9. Policy / Gate — authorization rules
10. Tests — feature, unit, component
11. Pint formatting
```

### 3.2 Model Standards

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(SomeObserver::class)]       // attach observer via attribute (Laravel 12)
class Example extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    // Always declare protected, not public
    protected $fillable = ['name', 'status', 'user_id'];

    protected function casts(): array    // method-based casts (Laravel 11+)
    {
        return [
            'status'     => ExampleStatus::class,  // enum cast
            'metadata'   => 'array',
            'published_at' => 'immutable_datetime',
        ];
    }

    // Relationships — always type-hinted
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes — chainable, named descriptively
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ExampleStatus::Active);
    }
}
```

**Rules:**

- Use ULIDs (`HasUlids`) instead of auto-increment for new public-facing resources.
- Use `declare(strict_types=1)` in every file.
- Use enum-backed casts for status/type columns.
- Never use `$guarded = []` — always declare `$fillable` explicitly.
- Always add `$timestamps = false` only when explicitly needed and justified.

### 3.3 FormRequest Standards

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Always check authorization here, never assume middleware covers it
        return $this->user()->can('create', Example::class);
    }

    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ExampleStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
        ];
    }

    // Use prepareForValidation for normalization, not controllers
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->name),
        ]);
    }
}
```

### 3.4 Action Class Pattern

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Example;

final class CreateExampleAction
{
    /**
     * @throws \Throwable
     */
    public function execute(ExampleData $data, User $actor): Example
    {
        // Authorization re-check inside action for defense-in-depth
        abort_unless($actor->can('create', Example::class), 403);

        return DB::transaction(function () use ($data, $actor) {
            $example = Example::create([
                'name'    => $data->name,
                'user_id' => $actor->id,
                'status'  => ExampleStatus::Draft,
            ]);

            // Trigger events, notifications, etc.
            event(new ExampleCreated($example));

            return $example;
        });
    }
}
```

**Rules:**

- Actions are `final` classes with a single `execute()` method.
- Wrap multi-step mutations in `DB::transaction()`.
- Authorization checked in BOTH FormRequest AND Action (defense in depth).
- Fire domain events inside the transaction so listeners roll back on failure.

### 3.5 Livewire Component Standards

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ExampleForm extends Component
{
    // Public properties — all reactive
    #[Locked]                       // prevents external tampering
    public string $exampleId = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required')]
    public string $status = '';

    // Computed — cached per render, refreshed on dependency change
    #[Computed]
    public function example(): Example
    {
        return Example::findOrFail($this->exampleId);
    }

    public function mount(string $exampleId): void
    {
        $this->authorize('update', Example::findOrFail($exampleId));
        $this->exampleId = $exampleId;
        $this->fill($this->example->only('name', 'status'));
    }

    public function save(): void
    {
        $this->authorize('update', $this->example);   // re-authorize on action
        $this->validate();

        (new UpdateExampleAction())->execute(
            $this->example,
            ExampleData::from($this->all()),
            auth()->user(),
        );

        $this->dispatch('example-updated');
        $this->success('Berhasil disimpan.');         // flash or toast helper
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.example-form');
    }
}
```

**Rules:**

- Use `#[Locked]` on IDs and values that must not be tampered by the browser.
- Use `#[Computed]` for derived data instead of re-querying in `render()`.
- Always call `$this->authorize()` inside `mount()` AND each mutating action.
- Use `$this->validate()` before any persistence call.
- Keep `render()` free of logic — it is only a view factory.
- Handle loading states in Blade with `wire:loading` and `wire:target`.

### 3.6 Blade + Alpine.js Integration Rules

```blade
{{-- Use wire:loading.attr="disabled" on submit buttons --}}
<x-button
    wire:click="save"
    wire:loading.attr="disabled"
    wire:target="save"
>
    <span wire:loading.remove wire:target="save">Simpan</span>
    <span wire:loading wire:target="save">Menyimpan...</span>
</x-button>

{{-- Confirmation pattern for destructive actions --}}
<div x-data="{ confirming: false }">
    <x-button @click="confirming = true" variant="danger">Hapus</x-button>
    <div x-show="confirming" x-cloak>
        <p>Anda yakin?</p>
        <x-button wire:click="delete" @click="confirming = false">Ya, hapus</x-button>
        <x-button @click="confirming = false">Batal</x-button>
    </div>
</div>
```

### 3.7 API Resource Standards (when building JSON responses)

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'status'     => $this->status->value,      // enum → string
            'created_at' => $this->created_at->toIso8601String(),

            // Conditional — only load if relationship was eager-loaded
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
```

---

## Phase 4 — Testing

### 4.1 Test Planning Template

Before writing tests, fill this out mentally:

```
Feature: [name]

Happy path:
  - [user does X → system returns Y]

Validation failures:
  - [missing field → 422 + correct error key]
  - [invalid enum value → 422]

Authorization failures:
  - [unauthenticated → 401/redirect]
  - [wrong role → 403]

Edge cases:
  - [empty list → empty state, not 500]
  - [concurrent update → handled gracefully]

Livewire interactions (if applicable):
  - [fill form → submit → see success message]
  - [invalid input → see validation error inline]
  - [destructive action → confirmation required]
```

### 4.2 Feature Test Template (Pest + Laravel)

```php
<?php

declare(strict_types=1);

use App\Models\Example;
use App\Models\User;

describe('ExampleController', function () {

    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    it('stores a new example when valid data is provided', function () {
        $this->actingAs($this->user)
            ->post(route('examples.store'), [
                'name'   => 'Test Example',
                'status' => 'draft',
            ])
            ->assertRedirect(route('examples.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('examples', [
            'name'    => 'Test Example',
            'user_id' => $this->user->id,
        ]);
    });

    it('returns 422 when name is missing', function () {
        $this->actingAs($this->user)
            ->postJson(route('examples.store'), ['status' => 'draft'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('returns 403 when user lacks permission', function () {
        $other = User::factory()->create();   // user with no permission

        $this->actingAs($other)
            ->post(route('examples.store'), ['name' => 'X', 'status' => 'draft'])
            ->assertForbidden();
    });

    it('returns 401 when unauthenticated', function () {
        $this->post(route('examples.store'), ['name' => 'X'])
            ->assertRedirect(route('login'));
    });
});
```

### 4.3 Livewire Component Test Template

```php
<?php

declare(strict_types=1);

use App\Livewire\ExampleForm;
use App\Models\Example;
use Livewire\Livewire;

describe('ExampleForm component', function () {

    it('pre-fills form fields from the model', function () {
        $example = Example::factory()->create(['name' => 'Existing Name']);

        Livewire::actingAs($example->user)
            ->test(ExampleForm::class, ['exampleId' => $example->id])
            ->assertSet('name', 'Existing Name');
    });

    it('saves successfully with valid data', function () {
        $example = Example::factory()->create();

        Livewire::actingAs($example->user)
            ->test(ExampleForm::class, ['exampleId' => $example->id])
            ->set('name', 'Updated Name')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('example-updated');

        expect($example->fresh()->name)->toBe('Updated Name');
    });

    it('shows validation error when name is empty', function () {
        $example = Example::factory()->create();

        Livewire::actingAs($example->user)
            ->test(ExampleForm::class, ['exampleId' => $example->id])
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    });

    it('throws 403 when unauthorized user tries to mount', function () {
        $example = Example::factory()->create();
        $other   = User::factory()->create();

        Livewire::actingAs($other)
            ->test(ExampleForm::class, ['exampleId' => $example->id])
            ->assertForbidden();
    });
});
```

### 4.4 Unit Test Template (Action class)

```php
<?php

declare(strict_types=1);

use App\Actions\CreateExampleAction;
use App\Data\ExampleData;
use App\Models\User;

describe('CreateExampleAction', function () {

    it('creates an example and fires an event', function () {
        Event::fake([ExampleCreated::class]);

        $user   = User::factory()->create();
        $data   = ExampleData::from(['name' => 'Test', 'status' => 'draft']);
        $action = new CreateExampleAction();

        $example = $action->execute($data, $user);

        expect($example)->toBeInstanceOf(Example::class)
            ->and($example->name)->toBe('Test')
            ->and($example->user_id)->toBe($user->id);

        Event::assertDispatched(ExampleCreated::class);
    });
});
```

### 4.5 Test Execution Order

```bash
# 1. Run only the new/changed test file first
php artisan test --compact tests/Feature/ExampleTest.php

# 2. Run full test suite to catch regressions
php artisan test --compact

# 3. Check for N+1 queries (add to AppServiceProvider::boot in testing env)
# Model::preventLazyLoading(! app()->isProduction());
```

---

## Phase 5 — Security Hardening Checklist

Copilot must verify each item. Mark ✓ done or ✗ not applicable with reason.

### Authorization

- [ ] Every route has `middleware('auth')` or equivalent.
- [ ] Every controller method or Livewire action calls `$this->authorize()` or
      uses a policy via `authorizeResource`.
- [ ] Policies cover `viewAny`, `view`, `create`, `update`, `delete`, and `restore`
      where applicable.
- [ ] Livewire components re-authorize in every mutating action, not just `mount()`.

### Input Validation

- [ ] Every field is validated before use.
- [ ] Enum fields use `Rule::enum()` — not raw string comparison.
- [ ] File uploads (if any) validate MIME type and size server-side.
- [ ] No `$request->all()` is passed directly to `create()` or `fill()` without
      explicit field allowlist.

### Mass Assignment

- [ ] All models declare `$fillable` explicitly — never `$guarded = []`.
- [ ] No untrusted array is spread directly into `Model::create()`.

### SQL / Query Safety

- [ ] No raw `DB::statement` with user-supplied values — use bindings.
- [ ] `whereIn` with user arrays is validated to expected types first.

### Sensitive Data

- [ ] Passwords and secrets never appear in logs (`log:clear` safe).
- [ ] API tokens use `makeHidden` or are excluded from Resources.
- [ ] Response bodies do not leak internal IDs, paths, or stack traces in
      production (`APP_DEBUG=false`).

### CSRF / XSS

- [ ] All non-API POST routes are covered by CSRF middleware.
- [ ] Blade outputs use `{{ }}` (escaped), never `{!! !!}` unless content is
      explicitly sanitized.

---

## Phase 6 — Performance Review

### Query Analysis

```php
// Temporarily add in development to catch N+1
\Illuminate\Support\Facades\DB::listen(function ($query) {
    logger($query->sql, $query->bindings);
});

// Or use Laravel Debugbar / Telescope
```

Checklist:

- [ ] New Livewire components eager-load required relationships in `#[Computed]`
      or `mount()`.
- [ ] List views use `paginate()` — never `get()` on unbounded collections.
- [ ] No `Model::all()` in loops, Livewire renders, or API resources.
- [ ] `#[Computed]` is used for values that are accessed multiple times per
      render cycle.
- [ ] Heavy operations (PDF, email, external API) are dispatched to queues.

### Livewire Re-render Optimization

- [ ] `wire:model.live` is used only where immediate feedback is needed;
      prefer `wire:model.blur` or `wire:model.lazy` for most text fields.
- [ ] `wire:key` is set on `@foreach` loops to prevent DOM diffing bugs.
- [ ] `#[Computed(persist: true)]` is used for expensive, rarely-changing
      computations (cache to Redis/file).

---

## Phase 7 — Code Quality Gates

### 7.1 Laravel Pint (Formatting)

```bash
# Check only changed files
vendor/bin/pint --dirty

# Apply fixes
vendor/bin/pint --dirty --format agent

# Verify nothing was left unfixed
vendor/bin/pint --test
```

### 7.2 Static Analysis (if PHPStan/Larastan is configured)

```bash
vendor/bin/phpstan analyse --memory-limit=512M
```

Fix all level-appropriate errors before delivery. Do not suppress errors without
a comment explaining why.

### 7.3 Final Pre-delivery Checklist

- [ ] All acceptance criteria produce passing tests.
- [ ] `php artisan test --compact` passes with no failures.
- [ ] `vendor/bin/pint --test` exits with code 0.
- [ ] No `dd()`, `dump()`, `var_dump()`, `Log::debug()` left in production paths.
- [ ] No hardcoded credentials, URLs, or environment-specific strings in source.
- [ ] Migrations are reversible and tested locally with `migrate:fresh`.
- [ ] `php artisan route:list` shows no unintended routes added.
- [ ] All new environment variables are documented in `.env.example`.

---

## Phase 8 — Handoff Report

Produce this report at task completion. Every section is mandatory.

```markdown
## Handoff Report — [Task Name]

### Outcome

[One sentence: what was built or fixed and the observed result.]

### Changes Made

| File                                                | Type     | Description                              |
| --------------------------------------------------- | -------- | ---------------------------------------- |
| app/Models/Example.php                              | Modified | Added `status` cast and `active` scope   |
| app/Livewire/ExampleForm.php                        | Created  | Multi-step form with auth and validation |
| database/migrations/xxxx_add_status_to_examples.php | Created  | Adds nullable status column              |
| tests/Feature/ExampleTest.php                       | Created  | 6 feature tests covering CRUD and auth   |

### Approach Rationale

[Why this approach over alternatives. Mention any documentation consulted.]

### Assumptions Made

- [Assumption 1 — file/line — impact if wrong]
- [Assumption 2 — file/line — impact if wrong]

### Tests Run

| Test file                         | Result           | Coverage area          |
| --------------------------------- | ---------------- | ---------------------- |
| tests/Feature/ExampleTest.php     | ✓ 6 passed       | CRUD, validation, auth |
| tests/Feature/ExampleFormTest.php | ✓ 4 passed       | Livewire component     |
| Full suite                        | ✓ No regressions | —                      |

### Risk Findings

#### Critical

[None / description + file:line + recommended fix]

#### High

[None / description + file:line + recommended fix]

#### Medium

[None / description + file:line + recommended fix]

#### Low

[None / description + file:line]

### Environment / Config Changes

- New env var: `EXAMPLE_SERVICE_URL` — added to `.env.example`
- New config key: `config/example.php` — deployed with config:cache

### Deferred Work and Follow-ups

- [ ] [Item] — reason deferred — estimated effort
- [ ] [Item] — blocked on [dependency]

### Performance Notes

[Any queries added, indexes required, or cache implications.]
```

---

## Quick Reference: Common Patterns

### Enum-backed Status Column

```php
// 1. Create enum
enum ExampleStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Archived  = 'archived';
}

// 2. Migration
$table->string('status')->default('draft');

// 3. Model cast (method-based, Laravel 11+)
protected function casts(): array
{
    return ['status' => ExampleStatus::class];
}

// 4. Validation
'status' => ['required', Rule::enum(ExampleStatus::class)],
```

### Pagination in Livewire

```php
use Livewire\WithPagination;

class ExampleList extends Component
{
    use WithPagination;

    #[Computed]
    public function examples(): LengthAwarePaginator
    {
        return Example::query()
            ->active()
            ->with('user')
            ->latest()
            ->paginate(15);
    }
}
```

```blade
{{ $this->examples->links() }}
```

### Optimistic UI Pattern

```blade
<div
    wire:click="togglePublish"
    x-data="{ published: @entangle('published') }"
    @click="published = !published"
>
    <span x-text="published ? 'Diterbitkan' : 'Draft'"></span>
</div>
```

### Soft Delete with Policy

```php
// Policy
public function restore(User $user, Example $example): bool
{
    return $user->id === $example->user_id;
}

// Controller
public function restore(Example $example): RedirectResponse
{
    $this->authorize('restore', $example);
    $example->restore();
    return redirect()->route('examples.index');
}

// Route — must include trashed models
Route::put('examples/{example}/restore', [ExampleController::class, 'restore'])
    ->withTrashed();
```

---

## Related Skills

- [livewire-development](../livewire-development/SKILL.md)
- [pest-testing](../pest-testing/SKILL.md)
- [tailwindcss-development](../tailwindcss-development/SKILL.md)
- [fluxui-development](../fluxui-development/SKILL.md)
