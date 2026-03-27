# Security Policy

## Scope

This bundle is **development-only** (see below). It anonymizes data for local/testing use and must not be enabled in production.

## Attack surface

- **Configuration** (YAML) defining which fields to anonymize.
- **Runtime data** passed through anonymizers (entities, arrays)—must not be logged with secrets in dev.

## Threats and mitigations

| Threat | Mitigation |
|--------|------------|
| Misuse in production | Documented as dev-only; do not register bundle in `prod`. |
| Data leakage in logs | Avoid verbose logging of raw PII in anonymization pipelines. |

## Dependencies

Run `composer audit` in consuming projects; keep the bundle updated.

## Logging

Do not log full production-like datasets or secrets during anonymization runs.

## Reporting a vulnerability

If you discover a security issue in this bundle, please report it responsibly:

- **Do not** open a public GitHub issue.
- Email the maintainers (e.g. via the address in `composer.json` or the repository's "Security" / "About" section) with a description of the issue and steps to reproduce.
- We will respond as soon as possible and work with you on a fix and disclosure.

## Important note

This bundle is **development-only** and must not be used in production. It is intended for anonymizing data in development and test environments. Do not install or enable it in production.

Thank you for helping keep this project and its users safe.

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
| **Limits / DoS** | Timeouts, size limits, rate limits where applicable. |

Record confirmation in the release PR or tag notes.

