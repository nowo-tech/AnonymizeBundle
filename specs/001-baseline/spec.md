# Feature Specification: AnonymizeBundle baseline (100% code coverage)

**Feature Branch**: `001-baseline`  
**Created**: 2026-07-07  
**Status**: Active  

**Package**: `nowo-tech/anonymize-bundle`  
**Configuration root**: `nowo_anonymize`  
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

### Core services

- **FR-SVC-001**: `AnonymizeService` orchestrates ORM/DBAL anonymization loops with batching and dry-run.
- **FR-SVC-002**: `PatternMatcher` applies include/exclude patterns from attributes.
- **FR-SVC-003**: `PreFlightCheckService` validates connections, entities, and environment before run.
- **FR-SVC-004**: `EnvironmentProtectionService` blocks prod execution.
- **FR-SVC-005**: `DatabaseExportService` + `SchemaService` support export CLI.
- **FR-SVC-006**: `AnonymizationHistoryService` persists run history.
- **FR-SVC-007**: `AnonymizeStatistics` / `AnonymizeStatisticsDisplay` export run metrics.

### Helpers

- **FR-HELPER-001**: `DbalHelper` / `OrmHelper` abstract connection and metadata access.
- **FR-INT-001**: `KernelParameterBagAdapter` reads kernel params without tight coupling.

---

## Success Criteria

- **SC-001**: **80/80** production files mapped in [`code-inventory.md`](code-inventory.md).
- **SC-002**: Config keys match [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md).
- **SC-003**: `composer qa` and demo `make release-check` pass.
- **SC-004**: PHPUnit covers Faker edge cases, environment guard, and batch iteration.

---

## Explicit non-goals

- Anonymizing production databases by default.
- Guarantees for MongoDB beyond documented commands unless extended in spec.

---

## Validation

`composer qa`, PHPUnit, PHPStan. Update spec + inventory when adding `src/` files.
