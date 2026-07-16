# Feature Specification: AnonymizeBundle baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Created**: 2026-07-07  
**Last updated**: 2026-07-15  
**Status**: Active  

**Package**: `nowo-tech/anonymize-bundle`  
**Configuration root**: `nowo_anonymize`  
**Runtime**: PHP **8.2+**, Symfony **6.1+** (see `composer.json` and README)  
**Code inventory**: [`code-inventory.md`](code-inventory.md)

---

## Summary

Doctrine entity anonymization for **dev/test** environments: PHP attributes mark fields, pluggable **Faker** generators produce replacement values, CLI commands run batch jobs, optional **export**, **history**, and **statistics**. Blocked in production via `EnvironmentProtectionService`.

---

## User Scenarios

### US-01 — Attribute-driven anonymization (P1)

**Given** entities/properties annotated with `@Anonymize` / `@AnonymizeProperty`, **When** `nowo:anonymize` runs, **Then** configured Faker types replace column values in batches.

### US-02 — Pattern and custom Fakers (P1)

**Given** pattern-based or service-backed faker types, **When** a property specifies `PatternBasedFaker` or a tagged Symfony service, **Then** `FakerFactory` resolves the implementation.

### US-03 — Safety and preflight (P1)

**Given** `APP_ENV=prod`, **When** anonymize or export runs, **Then** `EnvironmentProtectionService` aborts unless explicitly overridden in documented dev-only flows.

### US-04 — History and statistics (P2)

**Given** history/stats directories configured, **When** a run completes, **Then** `AnonymizationHistoryService` and statistics exporters persist auditable metadata under `history_dir` / `stats_output_dir`.

### US-05 — Database export (P2)

**Given** export enabled in config, **When** `nowo:anonymize:export-database` runs, **Then** compressed dumps land in `export.output_dir` using `filename_pattern`.

---

## Requirements

### Bundle & configuration

- **FR-BUNDLE-001**: `AnonymizeBundle` registers alias `nowo_anonymize`.
- **FR-CFG-001**: `Configuration` defines `locale`, `connections`, `dry_run`, `batch_size`, `stats_output_dir`, `history_dir`, `export.*`.
- **FR-CFG-002**: `AnonymizeExtension` wires services and parameters from merged config.
- **FR-DI-001**: `services.yaml` autowires commands, `AnonymizeService`, Faker factory, helpers.

### Attributes & traits

- **FR-ATTR-001**: `Anonymize` / `AnonymizeProperty` declare entity- and property-level rules.
- **FR-ATTR-002**: `AnonymizableTrait` documents optional entity helper for integrators.

### CLI

- **FR-CLI-001**: Commands `nowo:anonymize`, `nowo:anonymize:info`, history, export, column generators extend `AbstractCommand` with shared IO and preflight.

### Events

- **FR-EVT-001**: Before/after anonymize events (global and per-entity/property) allow extension without forking core service.

### Faker subsystem

- **FR-FAKER-001**: `FakerFactory` / `FakerFactoryInterface` resolve faker by type enum or service name.
- **FR-FAKER-002**: Built-in faker classes implement `FakerInterface` for documented types (email, iban, masking, hash preserve, etc.).
- **FR-FAKER-003**: `EmailFaker` supports `ensure_unique` (default `true`), `unique_field` (default `id`), and `unique_separator` (default `.`) so generated emails avoid unique constraint violations when a row record is available.

### Enums

- **FR-ENUM-001**: `FakerType` and `SymfonyService` enumerate built-in faker types and service-backed faker references.

### Core services

- **FR-SVC-001**: `AnonymizeService` orchestrates ORM/DBAL anonymization loops with batching and dry-run; passes the merged row record (original + in-pass anonymized values) to all fakers.
- **FR-SVC-002**: `PatternMatcher` applies include/exclude patterns from attributes.
- **FR-SVC-003**: `PreFlightCheckService` validates connections, entities, and environment before run.
- **FR-SVC-004**: `EnvironmentProtectionService` blocks prod execution.
- **FR-SVC-005**: `DatabaseExportService` + `SchemaService` support export CLI.
- **FR-SVC-006**: `AnonymizationHistoryService` persists run history.
- **FR-SVC-007**: `AnonymizeStatistics` / `AnonymizeStatisticsDisplay` export run metrics.

### Helpers

- **FR-HELPER-001**: `DbalHelper` / `OrmHelper` abstract connection and metadata access.
- **FR-INT-001**: `KernelParameterBagAdapter` reads kernel params without tight coupling.

### Repository hygiene (maintainers)

- **REQ-GIT-001**: Git history must not contain Cursor agent `Co-authored-by` trailers (`cursoragent@cursor.com`). Enforced by `.githooks/commit-msg`, `.scripts/check-no-cursor-coauthor.sh`, `.scripts/strip-cursor-coauthor-from-history.sh`, `make check-no-cursor-coauthor` (in `release-check`), and CI job `git-hygiene` with full history (`fetch-depth: 0`). Documented in [`docs/GITHUB_CI.md`](../../docs/GITHUB_CI.md), [`docs/CONTRIBUTING.md`](../../docs/CONTRIBUTING.md), and [`docs/RELEASE.md`](../../docs/RELEASE.md).

### Documentation

- **FR-DOCS-001**: All files under `docs/` are written in **English** (including examples and descriptive text).
- **FR-DOCS-002**: Integrator docs (`USAGE.md`, `CONFIGURATION.md`, `INSTALLATION.md`, `FAKERS.md`, `CHANGELOG.md`, `UPGRADING.md`) stay aligned with public API and configuration.
- **FR-DOCS-003**: Coverage improvement notes live in [`docs/TEST_COVERAGE_PROPOSAL.md`](../../docs/TEST_COVERAGE_PROPOSAL.md).

---

## Success Criteria

- **SC-001**: **80/80** production files mapped in [`code-inventory.md`](code-inventory.md).
- **SC-002**: Config keys match [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md).
- **SC-003**: `composer qa` and demo `make release-check` pass (includes `check-no-cursor-coauthor`).
- **SC-004**: PHPUnit covers Faker edge cases, environment guard, and batch iteration; orchestration classes excluded per [`phpunit.xml.dist`](../../phpunit.xml.dist) are covered by integration tests (see [`docs/TEST_COVERAGE_PROPOSAL.md`](../../docs/TEST_COVERAGE_PROPOSAL.md)).
- **SC-005**: `make check-no-cursor-coauthor` exits 0 on `main`; CI `git-hygiene` job passes on push and pull request.

---

## Explicit non-goals

- Anonymizing production databases by default.
- Guarantees for MongoDB beyond documented commands unless extended in spec.
- `git replace` as a substitute for cleaning co-author trailers on remotes.

---

## Validation

`composer qa`, PHPUnit, PHPStan, `make check-no-cursor-coauthor`, and CI `git-hygiene`. Update spec + inventory when adding `src/` files or maintainer hygiene tooling.
