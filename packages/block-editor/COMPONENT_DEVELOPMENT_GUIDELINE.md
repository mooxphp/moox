# Component Development Guideline

## Purpose

This package follows a strict reuse-first approach when implementing or extending editor components.

## Core Rule

When developing new components, always use existing functions, utilities, and patterns first.

- Reuse existing logic from current blocks and core modules whenever possible.
- Keep behavior consistent with already implemented components.
- Do not duplicate logic that already exists in the package.

## When New Functions Are Allowed

Create a new function only if no existing function or utility can cover the required behavior.

Before adding a new function:

1. Check related component files for similar logic.
2. Check `core` modules for reusable helpers.
3. Confirm that extending an existing function is not sufficient.

If a new function is required:

- Keep it small, focused, and clearly named.
- Place it in the most appropriate existing module.
- Follow existing coding style and conventions.
- Prefer composition over introducing parallel implementations.

## Practical Expectation

For new block components, the default approach is:

- Copy the proven structure of similar existing components.
- Wire into existing registries and render flows.
- Extend existing behavior paths before creating new ones.

Only introduce new behavior paths when reuse is not technically possible.
