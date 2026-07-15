# AnonymizeBundle Constitution

## Core Principles

### I. Documented integrator contract
Product behavior lives in `specs/001-baseline/spec.md`, `docs/SPEC-DRIVEN-DEVELOPMENT.md`, and integrator docs (`USAGE.md`, `CONFIGURATION.md`). Demos are illustrative unless promoted in the spec.

### II. Spec-first, test-proven
PHPUnit and PHPStan (and Vitest when frontend exists) are the mechanical proof. Behavioral changes require tests.

### III. 100% code inventory traceability
Every production file under `src/` must appear in `specs/001-baseline/code-inventory.md`. New files require spec updates in the same PR.

### IV. Cursor + Spec Kit
GitHub Spec Kit is initialized with **Cursor Agent** (`cursor-agent`). Skills live in `.cursor/skills/speckit-*`.

### V. Symfony compatibility
Follow declared PHP/Symfony ranges in `composer.json` and README badges (PHP **8.2+**, Symfony **6.1+**).

### VI. English documentation
All files under `docs/` must be written in English. See **FR-DOCS-001** in the baseline spec.

### VII. Git commit hygiene (REQ-GIT-001)
No Cursor agent `Co-authored-by` trailers in published history. Hooks, scripts, `release-check`, and CI `git-hygiene` enforce this. See [`docs/GITLAB_CI.md`](../docs/GITLAB_CI.md).

## Governance
Amendments update this file, baseline spec when principles affect behavior, and `CHANGELOG.md` when consumer-visible.

**Version**: 1.1.0 | **Ratified**: 2026-07-07 | **Last Amended**: 2026-07-15
