# OpenCode Agent Instructions

## Core Philosophy
*   **Signal over Noise**: Include only high-signal, repo-specific details. Exclude generic advice or obvious framework conventions (e.g., "always use PHPDoc" is generic).
*   **Verifiability**: Must be something an agent could miss without explicit help (e.g., a non-obvious command sequence, a key architectural constraint, a specific configuration quirk).
*   **Source of Truth**: Trust executable configuration files (`*config`, `*rules`) and CI/pre-commit hooks over prose documentation.

## 💡 Investigation Workflow (How to Investigate)
1.  **Read Order Hierarchy**: Check in this order:
    *   `README*`, root manifests (`composer.json`, `package.json`)
    *   Build/Test/Lint/Format/Typecheck/CodeGen configs (e.g., `phpunit.xml`, `tailwind.config.js`).
    *   CI workflows and pre-commit hooks (`.github/workflows/*.yml`, `.git/hooks/*`).
    *   Existing instruction files (`AGENTS.md`, `laravel-best-practices`, etc.).
2.  **Deep Dive**: If the architecture is unclear after config review, inspect a small, representative set of files focusing on **execution flow, boundaries, and wiring logic** (e.g., service providers, middleware definitions).
3.  **Conflict Resolution**: If docs conflict with config/scripts, *trust the executable source* (config/scripts) and document that discrepancy.

## 🎯 Information to Extract (What is High Signal?)
*   **Exact Commands**: Non-obvious, specific CLI commands (e.g., `php artisan cache:clear` vs. `php artisan config:clear`).
*   **Step Ordering**: Required sequences (e.g., `lint -> typecheck -> test` must be in order).
*   **Architectural Boundaries**: Define package ownership, primary entrypoints, and which systems communicate directly.
*   **Framework Quirks**: State non-default behaviors (e.g., required global middleware, specific data fetching patterns).
*   **Testing Quirks**: Mention necessary fixtures, setup prerequisites, or known flaky tests that need skipping/special handling.

## ❓ Asking Questions
Use the `?` tool **only** if repository evidence is exhausted and the following is missing:
*   Undocumented team conventions.
*   Branch/PR/release guidelines.
*   Missing setup prerequisites known to the team.

## ✍️ Writing Rules Summary
*   **Include**: Specific commands, architectural shortcuts, deviation from defaults.
*   **Exclude**: Generic advice, basic concepts (e.g., "use braces"), or anything that is too speculative.
*   **Style**: Use short, bulleted lists. If the repo is large, summarize *only* the structural facts that change agent workflow.

**Example Detail to Preserve**: When editing Filament components, always use `->visibility(fn (Get $get): bool => $get('type') === 'business')` for conditional logic.
*Last Updated: [Date of completion]*