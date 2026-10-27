# Security Policy

## Table of contents

- [Scope](#scope)
- [Attack surface](#attack-surface)
- [Threats and mitigations](#threats-and-mitigations)
- [Dependencies](#dependencies)
- [Logging](#logging)
- [Reporting a vulnerability](#reporting-a-vulnerability)
- [Important note](#important-note)
- [Remediations addressed](#remediations-addressed)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)
- [AI security audit](#ai-security-audit)

## Scope

This bundle is **development-only** (see below). It anonymizes data for local/testing use and must not be enabled in production.

## Attack surface

- **Configuration** (YAML) and **entity attributes** (`#[Anonymize]`, `#[AnonymizeProperty]`) deciding which tables and columns are overwritten, truncated or exported.
- **Console commands** (`nowo:anonymize:*`): write in place to the configured Doctrine connections, run external tools and write files (exports, history, statistics, generated scripts).
- **Subprocesses**: `mysqldump`, `pg_dump`, `mongodump`, `gzip`, `bzip2`, `tar` (and `which` / `where` to detect them).
- **Temporary credential files** for those tools, and the **output files**: database dumps (personal data until anonymized), run history, statistics.
- **Service locator** of custom fakers / entity anonymizers referenced by id from attributes.
- **Flex recipe** files copied into the host application.
- **Runtime data** passed through fakers (row values) — never logged.

## Threats and mitigations

| Threat | Mitigation |
|--------|------------|
| Misuse in production | Documented as dev-only; install with `--dev`, the recipe registers the bundle for `dev` / `test` only. |
| Commands in prod console | **All** `nowo:anonymize:*` commands run `EnvironmentProtectionService` (kernel env must be `dev`/`test`; prod config/bundles registration blocked). |
| `--env=dev` against production DSN | Configurable DSN/host **denylist** (`nowo_anonymize.environment_protection.blocked_dsn_substrings`) matched against `DATABASE_URL` / `MONGODB_URL` (and hosts) **and** Doctrine connection `url` / `host` / `dbname` params when ManagerRegistry is available. |
| Destructive anonymization (CLI) | Every run that is not `--dry-run` writes, **including `--stats-only`** (it only reduces the output). Non-interactive writes require `--force`, otherwise the command exits 1 without touching the database; `--interactive` only counts with a terminal. Every terminal run without `--force` confirms its target databases once (driver, host, database name — never credentials; default: no): through the interactive summary with `--interactive`, otherwise — including `--interactive --stats-only` — through a dedicated prompt. |
| Destructive TRUNCATE | Tables with `truncate=true` are emptied only after an explicit confirmation (default: no) or with `--force`, also with `--stats-only`; a declined or non-interactive prompt aborts with exit 1 before any change. |
| `hash_preserve` re-identification | `HashPreserveFaker` is a keyed **HMAC-SHA256/512** with a dedicated secret (`nowo_anonymize.hash_preserve.key`, default env `NOWO_ANONYMIZE_HASH_KEY`, at least 32 characters and 8 distinct characters; placeholders rejected, committed demo / test keys accepted only in the `test` environment); without a usable key the run stops at the pre-flight checks, before any write, instead of producing unsalted hashes. md5/sha1 are rejected. Its output is deterministic, so it is **pseudonymous, not anonymous** (whoever holds the key can confirm guesses); `length` and `preserve_format` reduce entropy further. The `copy` / `pattern_based` fallbacks use the same key. |
| Database export | `nowo:anonymize:export-db` only runs with `export.enabled: true`. Export **after** anonymizing: a dump contains whatever the database holds. Passwords reach `mysqldump` / `pg_dump` / `mongodump` through temporary `0600` credential files (removed after the run), never via argv (`ps`). |
| Subprocess injection | Tools run as an **argument vector** without a shell (`ArgvCommandRunnerInterface`, `SystemCommandRunner`); environment variables such as `PGPASSFILE` are passed to the child process. Custom runners implementing only `CommandRunnerInterface` receive a string in which every value is `escapeshellarg`-quoted. |
| Exported / generated files readable by others | The process umask is not changed (FrankenPHP-safe). Export, history and statistics directories are created `0700`, and existing ones (e.g. `0755` from ≤ 1.2) are tightened to `0700` when the process owns them. Dump files are pre-created `0600` before the dump tool writes into them (a planted symlink is replaced, not followed); tar archives likewise; ZIP archives are built in a fresh `0700` staging directory, made `0600` and then moved into place; `gzip` / `bzip2` keep the `0600` mode of the dump. History run files and `index.json`, `--stats-json` / `--stats-csv` files are `0600`. |
| Partial dumps left behind | A failed or timed-out `mysqldump` / `pg_dump` deletes its partial output; the temporary `mongodump` BSON directory is always removed — unless it pre-exists as a symlink, in which case the export is refused and nothing is deleted; failed `gzip` / `bzip2` / `tar` / zip runs delete the partial archive (the plain dump is kept and reported). Directory clean-up removes symlinks without following them, and symlinks inside a MongoDB dump are not archived. |
| Stats path traversal (`--stats-json` / `--stats-csv`) | Paths are resolved under `stats_output_dir`, canonicalized and **rejected** if they escape it lexically or through a symlinked parent directory, or if the target itself is a symlink. |
| History index tampering | Entries of `index.json` are only read or deleted (`--cleanup`) when they point at a regular `run_*.json` file directly inside the history directory. |
| Custom service resolution | Fakers / anonymizers are resolved from a closed compile-time locator (FakerInterface / EntityAnonymizerServiceInterface implementers, tagged services, public duck-typed services for BC — deprecated in 1.3), never from the whole container. |
| Generated MongoDB script injection | `nowo:anonymize:generate-mongo-field` rejects database / collection names outside `[A-Za-z0-9_.-]` (and `system.*`) and emits them as JSON string literals (`db.getSiblingDB(...)`, `db.getCollection(...)`). |
| `nowo:anonymize:info` data exposure | Reads only the columns used by patterns (no `SELECT *`, no rows at all without patterns) and masks option values whose name looks secret (`salt`, `key`, `secret`, `password`, `token`). |
| Error output leaking connection details | `nowo:anonymize:export-db` prints only the exception class of a failed export; the message is shown with `-vv`. |
| Flex recipe side effects | The recipe copies a dev-only config file and registers the bundle for `dev` / `test`; it no longer writes `APP_ENV` to the host `.env` (recipe 1.3; removed from 0.0.1 as well). |
| Hung export subprocesses (FrankenPHP/FPM) | `export.timeout` (default 180s) on Symfony Process; demo Caddy/PHP deadlines sit above it (REQ-RUNTIME-001). |
| Data leakage in logs | Row values, DSNs and exception messages are never logged (see [Logging](#logging)). |

Accepted residuals: bundle services keep `public: true` defaults for backward compatibility (they all implement the plugin interfaces, so this does not widen the locator); duck-typed custom fakers stay resolvable until 2.0 with a deprecation.

## Dependencies

Run `composer audit` in consuming projects; keep the bundle updated.

## Logging

Do not log full production-like datasets or secrets during anonymization runs.

`nowo:anonymize:run` and `nowo:anonymize:export-db` log through the injected PSR-3 logger (Monolog `app` channel by default; nothing when no logger is registered):

| Event | Level | Context |
| --- | --- | --- |
| Command started | `info` | `bundle: anonymize`, `action` (`anonymize` / `export_database`), `command`, `connections`, `dry_run` (anonymize only) |
| Command finished | `info` | same |
| Command did not complete (non-zero exit) | `warning` | same + `exit_code` |
| Command threw | `error` | same + `exception` (class name only) |

**Never logged:** DSNs, credentials, row values (original or anonymized), exception messages (they may contain DSNs) and file contents of exports.

## Reporting a vulnerability

If you discover a security issue in this bundle, please report it responsibly:

- **Do not** open a public GitHub issue.
- Email the maintainers (e.g. via the address in `composer.json` or the repository's "Security" / "About" section) with a description of the issue and steps to reproduce.
- We will respond as soon as possible and work with you on a fix and disclosure.

## Important note

This bundle is **development-only** and must not be used in production. It is intended for anonymizing data in development and test environments. Do not install or enable it in production.

Thank you for helping keep this project and its users safe.

## Remediations addressed

Previously accepted residuals that are now mitigated:

1. **DSN denylist only on env vars** — also inspects Doctrine DBAL connection parameters.
2. **Unsalted `hash_preserve`** — keyed HMAC with a dedicated key (1.3; replaces `default_salt` / `%kernel.secret%`).
3. **Stats export path escape** — `--stats-json` / `--stats-csv` cannot write outside `stats_output_dir` (symlinks included since 1.3).
4. **`--stats-only` bypassing `--force` and the TRUNCATE prompt** (since 1.0.33 / 1.1.0) — fixed in 1.3 (SEC-004 M1).
5. **World-readable exports / history / statistics, partial dumps** — owner-only files, failures clean up (1.3).

## Release security checklist (12.4.1)

Before tagging a release, confirm:

| Item | Notes |
|------|--------|
| **SECURITY.md** | This document is current and linked from the README where applicable. |
| **`.gitignore` and `.env`** | `.env` and local env files are ignored; no committed secrets. |
| **No secrets in repo** | No API keys, passwords, or tokens in tracked files. |
| **Recipe / Flex** | Default recipe or installer templates do not ship production secrets. |
| **Input / output** | Inputs validated; outputs escaped in Twig/templates where user-controlled. |
| **Dependencies** | `composer audit` run; issues triaged. |
| **Logging** | Logs do not print secrets, tokens, or session identifiers unnecessarily. |
| **Cryptography** | If used: keys from secure config; never hardcoded. |
| **Permissions / exposure** | Routes and admin features documented; roles configured for production. |
| **Limits / DoS** | `export.timeout` + PHP/Caddy hierarchy documented (REQ-RUNTIME-001). |
| **AI security audit (REQ-SEC-004)** | Grade **Pass (good)** / risk **Low** (2026-10-27, re-audit of `main` `b10f6d7` + fixes in PR #31, shipped in 1.3.0). See [AI security audit](#ai-security-audit). Re-audit when commands, environment protection, export, `hash_preserve` or the plugin locator change. |

Record confirmation in the release PR or tag notes.

## AI security audit

| Field | Value |
| --- | --- |
| Date | 2026-10-27 |
| Scope | `main` `b10f6d7` (PR #30 closed plugin locator) + hardening PR #31 (head `e544045`), released as 1.3.0 |
| Grade | Pass (good) |
| Risk | Low |
| Method | Claude Code static review of `src/` (commands, SQL building, export service and runners, history/statistics, environment protection, locator pass, fakers), Flex recipes, docs and demo env files; `--locale` autoload check on PHP 8.5.12; fix verification by diff review and bypass attempts (`--stats-only`/`-i`/`-n` combinations, symlinked stats/history/export paths, failed/timed-out tools, mongosh name injection, placeholder keys) |
| Findings (fixed in 1.3.0) | M1 `--stats-only` bypassed the non-interactive `--force` guard and the TRUNCATE prompt (1.0.33–1.2.x); L1 `hash_preserve` was `sha256(value . salt)` with `%kernel.secret%` (now HMAC with a dedicated key, quality-checked before any write); L2 world-readable dumps/history/statistics (now 0700/0600, pre-1.3 directories tightened, ZIPs staged privately); L3 partial dumps and ignored compression exit codes; L4 recipe wrote `APP_ENV=dev`; L5 unescaped names in the generated mongosh script; N2 `--interactive` without a terminal and `-i --stats-only` without confirmation; Info: shell strings (now argv), trusted history index paths, symlink-blind stats path, `SELECT *` + unmasked options in `info`, exception messages on export, TTY runs without target confirmation, duck-typed locator services (deprecated), symlinked Mongo dump directory |
| Open residuals | Destructive by design in dev/test; DSN denylist is substring-based and can be emptied; `hash_preserve` output is pseudonymous (treat the key like a password); anonymize before `export-db`; bundle services keep `public: true` |

