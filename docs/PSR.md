# PHP-FIG PSR evaluation (REQ-CS-007)

Package: `nowo-tech/anonymize-bundle` (`symfony-bundle`)

This document records which [PHP-FIG PSRs](https://www.php-fig.org/psr/) apply to this package.
Only contracts that add clear interoperability or maintainability value are **Adopted**.
Others are **N/A** (or already covered by Symfony) so the decision stays auditable.

## Baseline (always)

| PSR | Decision | How |
| --- | -------- | --- |
| PSR-12 (coding style) | **Adopted** | `@PSR12` in `.php-cs-fixer.dist.php` (Nowo REQ-CS-001). |
| PSR-4 (autoloading) | **Adopted** | `composer.json` `autoload` / `autoload-dev` PSR-4 map for package sources and tests. |

## Interface / contract PSRs

| PSR | Decision | Notes |
| --- | -------- | ----- |
| PSR-3 Logger | **N/A** | Logging calls exist but there is no public/injectable logger SPI yet; do not add `psr/log` without type-hints. |
| PSR-6 / PSR-16 Cache | **N/A** | No package-owned cache layer. |
| PSR-7 / PSR-17 HTTP messages | **N/A** | Symfony HttpFoundation (or no HTTP messages API) is the correct surface; a PSR-7 bridge would not help consumers. |
| PSR-18 HTTP client | **N/A** | No outbound HTTP client. |
| PSR-11 Container | **Adopted (narrow)** | Uses `Psr\Container\ContainerInterface` only as a **closed plugin/strategy registry** (documented service ids), not as a general service locator. |
| PSR-14 Event dispatcher | **Already satisfied via Symfony** | Uses Symfony `EventDispatcherInterface` / subscribers (PSR-14 compatible). No separate FIG dependency required. |
| PSR-15 HTTP middleware | **N/A** | Uses Symfony kernel listeners/subscribers (or has no HTTP middleware pipeline). |
| PSR-20 Clock | **Adopted** | Type-hint `Psr\Clock\ClockInterface` for time-sensitive logic. |

## Summary

- **Adopted beyond baseline:** PSR-11 Container, PSR-20 Clock
- **Rule:** do not add `psr/*` Composer dependencies without matching type-hints and DI wiring.
- **Re-evaluate** when the package gains logging, HTTP, cache, clock, or event SPIs.

---

_REQ-CS-007 evaluation date: 2026-08-21._
