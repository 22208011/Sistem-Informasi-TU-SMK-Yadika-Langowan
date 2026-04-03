---
name: livewire-development
description: "Develops reactive Livewire 4 components. Activates when creating, updating, or modifying Livewire components; working with wire:model, wire:click, wire:loading, or any wire: directives; adding real-time updates, loading states, or reactivity; debugging component behavior; writing Livewire tests; or when the user mentions Livewire, component, counter, or reactive UI."
license: MIT
metadata:
  author: laravel
---
<?php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
?>
# Livewire Development

## When to Apply

Activate this skill when:

- Creating or modifying Livewire components
- Using wire: directives (model, click, loading, sort, intersect)
- Implementing islands or async actions
- Writing Livewire component tests

## Documentation

Use ___SINGLE_BACKTICK___search-docs___SINGLE_BACKTICK___ for detailed Livewire 4 patterns and documentation.

## Basic Usage

### Creating Components

___BOOST_SNIPPET_0___

### Converting Between Formats

Use ___SINGLE_BACKTICK___<?php echo e($assist->artisanCommand('livewire:convert create-post')); ?>___SINGLE_BACKTICK___ to convert between single-file, multi-file, and class-based formats.

### Component Format Reference

| Format | Flag | Structure |
|--------|------|-----------|
| Single-file (SFC) | default | PHP + Blade in one file |
| Multi-file (MFC) | ___SINGLE_BACKTICK___--mfc___SINGLE_BACKTICK___ | Separate PHP class, Blade, JS, tests |
| Class-based | ___SINGLE_BACKTICK___--class___SINGLE_BACKTICK___ | Traditional v3 style class |
| View-based | ⚡ prefix | Blade-only with functional state |

### Single-File Component Example

___BOOST_SNIPPET_1___

## Livewire 4 Specifics

### Key Changes From Livewire 3

These things changed in Livewire 4, but may not have been updated in this application. Verify this application's setup to ensure you follow existing conventions.

- Use ___SINGLE_BACKTICK___Route::livewire()___SINGLE_BACKTICK___ for full-page components; config keys renamed: ___SINGLE_BACKTICK___layout___SINGLE_BACKTICK___ → ___SINGLE_BACKTICK___component_layout___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___lazy_placeholder___SINGLE_BACKTICK___ → ___SINGLE_BACKTICK___component_placeholder___SINGLE_BACKTICK___.
- ___SINGLE_BACKTICK___wire:model___SINGLE_BACKTICK___ now ignores child events by default (use ___SINGLE_BACKTICK___wire:model.deep___SINGLE_BACKTICK___ for old behavior); ___SINGLE_BACKTICK___wire:scroll___SINGLE_BACKTICK___ renamed to ___SINGLE_BACKTICK___wire:navigate:scroll___SINGLE_BACKTICK___.
- Component tags must be properly closed; ___SINGLE_BACKTICK___wire:transition___SINGLE_BACKTICK___ now uses View Transitions API (modifiers removed).
- JavaScript: ___SINGLE_BACKTICK___$wire.$js('name', fn)___SINGLE_BACKTICK___ → ___SINGLE_BACKTICK___$wire.$js.name = fn___SINGLE_BACKTICK___; ___SINGLE_BACKTICK___commit___SINGLE_BACKTICK___/___SINGLE_BACKTICK___request___SINGLE_BACKTICK___ hooks → ___SINGLE_BACKTICK___interceptMessage()___SINGLE_BACKTICK___/___SINGLE_BACKTICK___interceptRequest()___SINGLE_BACKTICK___.

### New Features

- Component formats: single-file (SFC), multi-file (MFC), view-based components.
- Islands (___SINGLE_BACKTICK___@island___SINGLE_BACKTICK___) for isolated updates; async actions (___SINGLE_BACKTICK___wire:click.async___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___#[Async]___SINGLE_BACKTICK___) for parallel execution.
- Deferred/bundled loading: ___SINGLE_BACKTICK___defer___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___lazy.bundle___SINGLE_BACKTICK___ for optimized component loading.

| Feature | Usage | Purpose |
|---------|-------|---------|
| Islands | ___SINGLE_BACKTICK___@island(name: 'stats')___SINGLE_BACKTICK___ | Isolated update regions |
| Async | ___SINGLE_BACKTICK___wire:click.async___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___#[Async]___SINGLE_BACKTICK___ | Non-blocking actions |
| Deferred | ___SINGLE_BACKTICK___defer___SINGLE_BACKTICK___ attribute | Load after page render |
| Bundled | ___SINGLE_BACKTICK___lazy.bundle___SINGLE_BACKTICK___ | Load multiple together |

### New Directives

- ___SINGLE_BACKTICK___wire:sort___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___wire:intersect___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___wire:ref___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___.renderless___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___.preserve-scroll___SINGLE_BACKTICK___ are available for use.
- ___SINGLE_BACKTICK___data-loading___SINGLE_BACKTICK___ attribute automatically added to elements triggering network requests.

| Directive | Purpose |
|-----------|---------|
| ___SINGLE_BACKTICK___wire:sort___SINGLE_BACKTICK___ | Drag-and-drop sorting |
| ___SINGLE_BACKTICK___wire:intersect___SINGLE_BACKTICK___ | Viewport intersection detection |
| ___SINGLE_BACKTICK___wire:ref___SINGLE_BACKTICK___ | Element references for JS |
| ___SINGLE_BACKTICK___.renderless___SINGLE_BACKTICK___ | Component without rendering |
| ___SINGLE_BACKTICK___.preserve-scroll___SINGLE_BACKTICK___ | Preserve scroll position |

## Best Practices

- Always use ___SINGLE_BACKTICK___wire:key___SINGLE_BACKTICK___ in loops
- Use ___SINGLE_BACKTICK___wire:loading___SINGLE_BACKTICK___ for loading states
- Use ___SINGLE_BACKTICK___wire:model.live___SINGLE_BACKTICK___ for instant updates (default is debounced)
- Validate and authorize in actions (treat like HTTP requests)

## Configuration

- ___SINGLE_BACKTICK___smart_wire_keys___SINGLE_BACKTICK___ defaults to ___SINGLE_BACKTICK___true___SINGLE_BACKTICK___; new configs: ___SINGLE_BACKTICK___component_locations___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___component_namespaces___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___make_command___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___csp_safe___SINGLE_BACKTICK___.

## Alpine & JavaScript

- ___SINGLE_BACKTICK___wire:transition___SINGLE_BACKTICK___ uses browser View Transitions API; ___SINGLE_BACKTICK___$errors___SINGLE_BACKTICK___ and ___SINGLE_BACKTICK___$intercept___SINGLE_BACKTICK___ magic properties available.
- Non-blocking ___SINGLE_BACKTICK___wire:poll___SINGLE_BACKTICK___ and parallel ___SINGLE_BACKTICK___wire:model.live___SINGLE_BACKTICK___ updates improve performance.

For interceptors and hooks, see [reference/javascript-hooks.md](reference/javascript-hooks.md).

## Testing

___BOOST_SNIPPET_2___

## Verification

1. Browser console: Check for JS errors
2. Network tab: Verify Livewire requests return 200
3. Ensure ___SINGLE_BACKTICK___wire:key___SINGLE_BACKTICK___ on all ___SINGLE_BACKTICK___@foreach___SINGLE_BACKTICK___ loops

## Common Pitfalls

- Missing ___SINGLE_BACKTICK___wire:key___SINGLE_BACKTICK___ in loops → unexpected re-rendering
- Expecting ___SINGLE_BACKTICK___wire:model___SINGLE_BACKTICK___ real-time → use ___SINGLE_BACKTICK___wire:model.live___SINGLE_BACKTICK___
- Unclosed component tags → syntax errors in v4
- Using deprecated config keys or JS hooks
- Including Alpine.js separately (already bundled in Livewire 4)
<?php /**PATH D:\Hizkia Reppi\Developer\Fullstack Development\Sistem-Informasi-TU-SMK-Yadika-Langowan\storage\framework\views/51a27161aff29899ae2c0bb7340ff496.blade.php ENDPATH**/ ?>