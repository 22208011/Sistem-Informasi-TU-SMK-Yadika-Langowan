---
name: volt-development
description: "Develops single-file Livewire components with Volt. Activates when creating Volt components, converting Livewire to Volt, working with ___VOLT_DIRECTIVE___ directive, functional or class-based Volt APIs; or when the user mentions Volt, single-file components, functional Livewire, or inline component logic in Blade files."
license: MIT
metadata:
  author: laravel
---
<?php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
?>
# Volt Development

## When to Apply

Activate this skill when:

- Creating Volt single-file components
- Converting traditional Livewire components to Volt
- Testing Volt components

## Documentation

Use ___SINGLE_BACKTICK___search-docs___SINGLE_BACKTICK___ for detailed Volt patterns and documentation.

## Basic Usage

Create components with ___SINGLE_BACKTICK___<?php echo e($assist->artisanCommand('make:volt [name] [--test] [--pest]')); ?>___SINGLE_BACKTICK___.

Important: Check existing Volt components to determine if they use functional or class-based style before creating new ones.

### Functional Components

___BOOST_SNIPPET_0___

### Class-Based Components

___BOOST_SNIPPET_1___

## Testing

Tests go in existing Volt test directory or ___SINGLE_BACKTICK___tests/Feature/Volt___SINGLE_BACKTICK___:

___BOOST_SNIPPET_2___

## Verification

1. Check existing components for functional vs class-based style
2. Test component with ___SINGLE_BACKTICK___Volt::test()___SINGLE_BACKTICK___

## Common Pitfalls

- Not checking existing style (functional vs class-based) before creating
- Forgetting ___SINGLE_BACKTICK______VOLT_DIRECTIVE______SINGLE_BACKTICK___ directive wrapper
- Missing ___SINGLE_BACKTICK___--test___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___--pest___SINGLE_BACKTICK___ flag when tests are needed
<?php /**PATH D:\Hizkia Reppi\Developer\Fullstack Development\Sistem-Informasi-TU-SMK-Yadika-Langowan\storage\framework\views/1c21df76db36ebbea6edcfa336d96d29.blade.php ENDPATH**/ ?>