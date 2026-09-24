# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/anonymize-bundle` (`symfony-bundle`) |
| Audited revision | `v1.0.47` |
| Audit date | 2026-09-24 |
| Method | Manual review of `src/` + remediation of W-01 / W-02 / W-03 |
| **Verdict** | — **Not applicable** for normal use (CLI / `dev`–`test` only). If an integrator calls services from HTTP / a long-lived process with **kernel not reset**: residual risk is **Low** (W-04 timeout wiring, W-05 `mt_srand` hygiene only). W-01–W-03 **closed**. |

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A bundle that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

### Why the bundle does not run in the worker

- The only entry points are six console commands (`src/Command/*Command.php`, `#[AsCommand]`, lazy). Console commands run in a separate `bin/console` CLI process, never in the FrankenPHP HTTP worker.
- `src/` contains no event listener/subscriber, controller, route, form type, Twig extension, Doctrine listener or Messenger handler, so no bundle code is executed during an HTTP request.
- The bundle is meant to be enabled only for `dev`/`test` (`src/AnonymizeBundle.php`, demo `config/bundles.php`), and `EnvironmentProtectionService` refuses to run outside `dev`/`test`.
- Services are still **registered** in the HTTP container (`services.yaml` sets `public: true`) and can be autowired by application code. Findings below describe that edge case.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ | Locale overrides use a **local** generator (W-01 closed); `AnonymizeService::$fakerCache` bounded |
| Static properties / `static` locals | ✅ | None; helpers only have pure static methods |
| `ResetInterface` / `kernel.reset` coverage | ✅ | `AnonymizeStatistics` implements `ResetInterface` (W-03 closed; autoconfigure → `kernel.reset`) |
| Request / user / locale captured in services | ✅ | Faker locale from `%nowo_anonymize.locale%`, not the request |
| Superglobals / env mutation | ✅ | `$_SERVER` / `getenv()` at call time only |
| Doctrine / EntityManager | ✅ | CLI; transactions + rollback; no entities stored in services |
| Output / headers / `exit` | ✅ | Commands use `SymfonyStyle` only |
| Resources held open | ✅ | `file_put_contents` / Process, not retained |
| Memory growth across requests | ✅ | `HashFaker` no longer uses Faker `unique()` (W-02 closed) |
| Blocking I/O and timeouts | ⚠️ Low | Container `DatabaseExportService` still defaults to 180 s (W-04) |
| Third-party static state | ⚠️ Low | `ShuffleFaker` still touches `mt_srand` briefly (W-05) |
| PHPStan FrankenPHP rulesets | ✅ | `ruleset-classic.neon` + `ruleset-worker.neon` in `phpstan.neon.dist` |

## Services reviewed (edge case: HTTP / long-lived)

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| `AnonymizeService` | yes | `$fakerCache` bounded | ✅ | ✅ |
| `FakerFactory` | yes | none (`readonly`) | ✅ | ✅ |
| `AnonymizeStatistics` | yes | accumulators; **`ResetInterface`** | ✅ | ✅ under A; under B empty unless injected across requests without reset — prefer `new` or rely on A |
| `AnonymizationHistoryService` | yes | none after construction | ✅ | ✅ |
| `DatabaseExportService` | yes | none; default timeout 180 s | ✅ (W-04) | ✅ (W-04) |
| `EnvironmentProtectionService` | yes | none (`readonly`) | ✅ | ✅ |
| `AddressFaker` / `CountryFaker` / `LanguageFaker` | yes | constructor generator only; overrides are local | ✅ | ✅ |
| `HashFaker` | yes | Faker RNG only (no `unique()` map) | ✅ | ✅ |
| `ShuffleFaker` | yes | none; brief `mt_srand` | ✅ (W-05) | ✅ (W-05) |
| Other fakers | yes | constructor generator or none | ✅ | ✅ |
| Console commands | lazy | CLI only | N/A | N/A |

## Closed findings (2026-09-24)

### W-01 — Locale-switching fakers (closed)

- **Was:** `$this->faker = Factory::create(...)` on shared services when `country` / `locale` options were set.
- **Fix:** use a local `$faker` variable in `AddressFaker`, `CountryFaker`, `LanguageFaker`; never reassign the property.
- **Tests:** `*DoesNotMutateSharedGenerator` in unit tests.

### W-02 — `HashFaker` + `unique()` growth (closed)

- **Was:** `$this->faker->unique()->text(100)` accumulated seen values forever on a shared service.
- **Fix:** use `$this->faker->text(100)` plus `randomNumber`; hash entropy already avoids meaningful collisions.

### W-03 — `AnonymizeStatistics` without reset (closed)

- **Was:** shared accumulator without `ResetInterface`.
- **Fix:** `implements ResetInterface` (existing `reset()`); autoconfigure tags `kernel.reset`. CLI still uses `new AnonymizeStatistics()` in the command.

## Open residuals (Low)

### W-04 — Container `DatabaseExportService` ignores `export.timeout`

Wire `$timeoutSeconds: '%nowo_anonymize.export.timeout%'` in `services.yaml` when desired.

### W-05 — `ShuffleFaker` reseeds global `mt_rand`

Prefer `Random\Randomizer` instead of `mt_srand()` for hygiene; window is tiny and seed is restored.

## Usage recommendations in worker mode

- Keep the bundle registered only for `dev` / `test`.
- Run anonymization/exports only via `bin/console`.
- Custom app fakers used outside CLI must stay stateless or implement `ResetInterface`.

## Re-audit triggers

Re-run when adding: HTTP/Messenger/Twig entry points; new mutable properties on shared fakers/services; Faker `unique()`; caches keyed by user data; `$_ENV` / `putenv` / `ini_set`.
