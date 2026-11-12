# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/anonymize-bundle` (`symfony-bundle`) |
| Audited revision | `v1.6.1` (first audit `v1.0.47`; REQ-RUNTIME-002 re-audit for 1.6.1) |
| Audit date | 2026-09-24; re-audited 2026-11-12 |
| Method | Manual review of `src/` + remediation of W-01 / W-02 / W-03 (2026-09-24) and W-05 / W-06 (1.6.1); one-kernel worker test without the services resetter; Igor in CI |
| Remediation (2026-11-12, 1.6.1) | W-05 closed: seeded `ShuffleFaker` uses a local `Random\Randomizer(new Mt19937($seed))` (same output as 1.6.0). W-06 closed: `AnonymizeConsoleBoundaryListener` clears the `AnonymizeService` faker cache at the outermost `console.terminate`. Worker test: `tests/Integration/Kernel/WorkerKeptAliveKernelTest.php` |
| Remediation (2026-11-12, 1.6.2, audits NM / NL L1) | Console bookkeeping in `Worker\UnitOfWorkFrames` at `PHP_INT_MAX` (frames keyed by Command + input, failing frames healed at the next command); the outermost run also clears the faker cache at its start |
| **Verdict** | ✅ **Safe with kernel reset false** (scenario B, and A) — the bundle runs from console commands (normally `dev` / `test` only); in a process that keeps one kernel without the services resetter, every command starts with an empty faker cache, no global RNG state is changed, and memory stays flat over many runs. Residual: W-04 (timeout wiring, Low). |

## Table of contents

- [Execution model assumed](#execution-model-assumed)
  - [Why the bundle does not run in the worker](#why-the-bundle-does-not-run-in-the-worker)
- [Summary](#summary)
- [Services reviewed (edge case: HTTP / long-lived)](#services-reviewed-edge-case-http--long-lived)
- [Closed findings (2026-09-24)](#closed-findings-2026-09-24)
  - [W-01 — Locale-switching fakers (closed)](#w-01--locale-switching-fakers-closed)
  - [W-02 — `HashFaker` + `unique()` growth (closed)](#w-02--hashfaker--unique-growth-closed)
  - [W-03 — `AnonymizeStatistics` without reset (closed)](#w-03--anonymizestatistics-without-reset-closed)
- [Closed findings (2026-11-12, 1.6.1)](#closed-findings-2026-11-12-161)
  - [W-05 — `ShuffleFaker` reseeds global `mt_rand` (closed)](#w-05--shufflefaker-reseeds-global-mt_rand-closed)
  - [W-06 — Faker cache cleared only by `kernel.reset` (closed)](#w-06--faker-cache-cleared-only-by-kernelreset-closed)
- [Open residuals (Low)](#open-residuals-low)
  - [W-04 — Container `DatabaseExportService` ignores `export.timeout`](#w-04--container-databaseexportservice-ignores-exporttimeout)
- [Worker test](#worker-test)
- [Usage recommendations in worker mode](#usage-recommendations-in-worker-mode)
- [Re-audit triggers](#re-audit-triggers)

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A bundle that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

### Why the bundle does not run in the worker

- The only entry points are six console commands (`src/Command/*Command.php`, `#[AsCommand]`, lazy). Console commands run in a separate `bin/console` CLI process, never in the FrankenPHP HTTP worker.
- `src/` contains no controller, route, form type, Twig extension, Doctrine listener or Messenger handler, and its only event subscriber (`AnonymizeConsoleBoundaryListener`, 1.6.1) listens to console events, so no bundle code is executed during an HTTP request.
- The bundle is meant to be enabled only for `dev`/`test` (`src/AnonymizeBundle.php`, demo `config/bundles.php`), and `EnvironmentProtectionService` refuses to run outside `dev`/`test`.
- Services are still **registered** in the HTTP container (`services.yaml` sets `public: true`) and can be autowired by application code. Findings below describe that edge case.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ | Locale overrides use a **local** generator (W-01 closed); `AnonymizeService::$fakerCache` bounded (one faker per type) and cleared at the outermost `console.terminate` (W-06, 1.6.1) and by `kernel.reset` |
| Boundary events (no `kernel.reset` needed) | ✅ | `EventListener\AnonymizeConsoleBoundaryListener` (1.6.1; frames since 1.6.2): `console.command` / `console.error` / `console.terminate` at `PHP_INT_MAX` keep one frame per console run (Command + input); the outermost run clears the faker cache at its start and at its `console.terminate` (-4096) |
| Static properties / `static` locals | ✅ | Helpers only have pure static methods. Since 1.6 the `@internal` `Event\Internal\EventOrmState` keeps a static `WeakMap` keyed by event objects (the Doctrine objects behind the deprecated event getters): entries are collected with their event, so nothing survives the run |
| `ResetInterface` / `kernel.reset` coverage | ✅ | `AnonymizeStatistics` implements `ResetInterface` (W-03 closed; autoconfigure → `kernel.reset`) |
| Request / user / locale captured in services | ✅ | Faker locale from `%nowo_anonymize.locale%`, not the request |
| Superglobals / env mutation | ✅ | `$_SERVER` / `getenv()` at call time only |
| Doctrine / EntityManager | ✅ | CLI; transactions + rollback; no entities stored in services |
| Output / headers / `exit` | ✅ | Commands use `SymfonyStyle` only |
| Resources held open | ✅ | `file_put_contents` / Process, not retained |
| Memory growth across requests | ✅ | `HashFaker` no longer uses Faker `unique()` (W-02 closed) |
| Blocking I/O and timeouts | ⚠️ Low | Container `DatabaseExportService` still defaults to 180 s (W-04) |
| Third-party / global state | ✅ | Since 1.6.1 a seeded `ShuffleFaker` uses a local `Random\Randomizer` (W-05 closed); unseeded shuffles read the global generator without reseeding it |
| Worker test (kernel reset false) | ✅ | `tests/Integration/Kernel/WorkerKeptAliveKernelTest.php` (see [Worker test](#worker-test)); Igor runs in CI since 1.6.1 |
| PHPStan FrankenPHP rulesets | ✅ | `ruleset-classic.neon` + `ruleset-worker.neon` in `phpstan.neon.dist` |

## Services reviewed (edge case: HTTP / long-lived)

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| `AnonymizeService` | yes | `$fakerCache` (faker instances per type, no row data) | ✅ reset | ✅ cleared at the outermost `console.terminate` (1.6.1) |
| `AnonymizeConsoleBoundaryListener` (1.6.1, `@internal`) | yes | a private `Worker\UnitOfWorkFrames` since 1.6.2 (console frames bounded by the nesting depth, per-event verdicts in a `WeakMap`; no request data; `reset()` exists, not tagged `kernel.reset`) | ✅ | ✅ |
| `FakerFactory` | yes | none (`readonly`) | ✅ | ✅ |
| `AnonymizeStatistics` | yes | accumulators; **`ResetInterface`** | ✅ | ✅ under A; under B empty unless injected across requests without reset — prefer `new` or rely on A |
| `AnonymizationHistoryService` | yes | none after construction | ✅ | ✅ |
| `DatabaseExportService` | yes | none; default timeout 180 s | ✅ (W-04) | ✅ (W-04) |
| `EnvironmentProtectionService` | yes | none (`readonly`) | ✅ | ✅ |
| `AddressFaker` / `CountryFaker` / `LanguageFaker` | yes | constructor generator only; overrides are local | ✅ | ✅ |
| `HashFaker` | yes | Faker RNG only (no `unique()` map) | ✅ | ✅ |
| `ShuffleFaker` | yes | none; seeded shuffles on a local `Mt19937` engine (1.6.1) | ✅ | ✅ |
| Other fakers | yes | constructor generator or none | ✅ | ✅ |
| Console commands | lazy | CLI only | N/A | N/A |
| `LegacyEntityAnonymizerAdapter` (1.6, `@internal`) | no | built per class and per run around a deprecated-SPI anonymizer; never a container service | N/A | N/A |

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

## Closed findings (2026-11-12, 1.6.1)

### W-05 — `ShuffleFaker` reseeds global `mt_rand` (closed)

- **Was:** a seeded shuffle called `mt_srand($seed)`, then `mt_srand()`: the process-wide generator was reseeded at
  random afterwards, so a caller's own seeded `mt_rand()` sequence (Faker's `seed()` uses it) was lost.
- **Fix:** `src/Faker/ShuffleFaker.php` shuffles with `(new Random\Randomizer(new Random\Engine\Mt19937((int) $seed)))->shuffleArray()`.
  Same output as 1.6.0 for the same seed and pool (Mt19937 + the same Fisher-Yates shuffle);
  `ShuffleFakerSeedEquivalenceTest` compares it with the 1.6.0 algorithm and pins recorded outputs.

### W-06 — Faker cache cleared only by `kernel.reset` (closed)

- **Was:** `AnonymizeService::$fakerCache` implemented `ResetInterface` only; with kernel reset false and no services
  resetter the faker instances of one run were reused by the next command in the same process.
- **Fix:** `src/EventListener/AnonymizeConsoleBoundaryListener.php` clears it at the outermost `console.terminate`
  (nesting depth counter, so a command run from another command keeps the outer run's fakers).
- **Audit NM / NL L1 (1.6.2, Low):** the depth counter (`++` at 4096, `--` at -4096) leaked when a console listener in
  between threw or stopped propagation, so the outermost command of a long-lived process stopped clearing the cache;
  reverse case: a nested command whose `console.command` was stopped above 4096 cleared the outer run's fakers mid-run.
  Now `Worker\UnitOfWorkFrames` fed at `PHP_INT_MAX` (audits NL2-v / NL2-v2 design: frames keyed by Command + input, a
  terminate without a frame never removes another run's frame, a frame left failing by a throwing error listener is
  dropped at the next command), and the outermost run also clears at its START. Tests: `AnonymizeConsoleBoundaryListenerTest`
  (real console `Application`), `UnitOfWorkFramesTest`.

## Open residuals (Low)

### W-04 — Container `DatabaseExportService` ignores `export.timeout`

Wire `$timeoutSeconds: '%nowo_anonymize.export.timeout%'` in `services.yaml` when desired.

## Worker test

`tests/Integration/Kernel/WorkerKeptAliveKernelTest.php` (REQ-RUNTIME-002) boots **one** `AnonymizeTestKernel` (DDD
host: XML-mapped `Patient`, `nowo_anonymize.entities`) and never calls the services resetter. It runs
`nowo:anonymize:run --force` through `Application::run()` (so `console.command` / `console.terminate` fire)
**N = 120** times over two entity sets in alternation (`alpha`: 3 rows, `beta`: 4 rows, one excluded row each; the
table is rewritten with DBAL before each run). After every run: exactly the current set's rows exist, the excluded row is
unchanged, every other row is anonymized (e-mail replaced and never one of either set, constants applied, a NULL cell
left NULL, `anonymized = 1`), the seeded `shuffle` city is the same on every run, and `AnonymizeService::$fakerCache` is
empty. Memory margin: less than 2 MiB growth after a 20-run warm-up. Unit tests: `AnonymizeConsoleBoundaryListenerTest`
(nesting), `ShuffleFakerSeedEquivalenceTest` (same outputs as 1.6.0, global `mt_rand()` untouched),
`AnonymizeServiceSqliteTest::testResetClearsTheFakerCache` (`reset()`).

## Usage recommendations in worker mode

- Keep the bundle registered only for `dev` / `test`.
- Run anonymization/exports only via `bin/console` (or `Application::run()` in-process: the console events clear the
  faker cache). If you call `AnonymizeService` from your own long-lived process outside a console command, call
  `reset()` between units of work.
- Custom app fakers used outside CLI must stay stateless or clear their state at the request / command boundary
  (`ResetInterface` alone is not enough with kernel reset false).
- **Host entity managers (REQ-RUNTIME-002 rule 7).** The bundle reads and writes rows with DBAL on the host's
  connections and keeps no entity in a service. A host that disables or bypasses the services resetter must still clear
  its own entity managers at every boundary; otherwise entities it loaded before an anonymization run keep their
  pre-anonymization values in its identity map.

## Re-audit triggers

Re-run when adding: HTTP/Messenger/Twig entry points; new mutable properties on shared fakers/services; Faker `unique()`; caches keyed by user data; `$_ENV` / `putenv` / `ini_set`.
