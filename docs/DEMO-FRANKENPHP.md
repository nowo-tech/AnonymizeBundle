# Demo applications with FrankenPHP (development and production)

This document describes how the bundle's demo applications run under **FrankenPHP** in Docker, and how to reproduce **development** (no cache, changes visible on refresh) and **production** (worker mode, cache enabled) configurations. The same approach can be used in other Symfony bundles or applications that ship a FrankenPHP-based demo.

## Contents

- [Overview](#overview)
- [What the demos include](#what-the-demos-include)
- [Timeouts (REQ-RUNTIME-001)](#timeouts-req-runtime-001)
- [Development configuration](#development-configuration)
- [Production configuration](#production-configuration)
- [Switching between development and production](#switching-between-development-and-production)
- [Reproducing in another bundle](#reproducing-in-another-bundle)
- [Troubleshooting](#troubleshooting)

---

## Overview

**The `demo/` folder is not shipped when the bundle is installed** (e.g. via `composer require nowo-tech/anonymize-bundle`). It is excluded from the Composer package (via `archive.exclude` in the bundle's `composer.json`). The demo applications exist only in the bundle's source repository and are intended for development, testing, and documentation. To run or modify the demos, use a clone of the bundle repository.

The demos use:

- **FrankenPHP** (Caddy + PHP) in a single container.
- **Docker Compose** with the app and the parent bundle mounted as volumes (`../..` → `/var/anonymize-bundle`).
- **Two Caddyfiles**: `Caddyfile` (production, with worker) and `Caddyfile.dev` (development, no worker).
- An **entrypoint** script that selects the Caddyfile from **`FRANKENPHP_MODE`** (`classic` | `worker`), defined in the demo **`.env`** / `.env.example` and passed by Compose (not baked into the Dockerfile). **Default is `worker`.** Edit `.env` and run `docker compose up -d` (recreate) to switch modes without rebuilding. If unset, the entrypoint uses `worker`.
- **Symfony 8 demo on the latest PHP available** in official FrankenPHP images when constraints allow (currently **PHP 8.5** → `dunglas/frankenphp:1-php8.5`). The Symfony 7 demo stays on PHP 8.2 to match that major.

There are demos for **Symfony 7** and **8** (e.g. **demo/symfony7**, **demo/symfony8**). Each has its own Dockerfile, docker-compose.yml and Makefile. From the bundle root you run e.g. `make -C demo/symfony8 up` (see the demo's README for the URL and port).

## Timeouts (REQ-RUNTIME-001)

Database export shells out to `mysqldump` / `pg_dump` / `mongodump` / compression tools via Symfony Process. Timeouts are layered so the **innermost** deadline fires first and FrankenPHP workers are not left blocked:

| Layer | Default | Role |
|-------|---------|------|
| `nowo_anonymize.export.timeout` | **180s** | Process wall-clock **and** idle timeout; on expiry the runner `stop(0)`s the subprocess |
| PHP `max_execution_time` / `max_input_time` | **240s** | Set in demo Caddyfiles via `frankenphp { php_ini … }` — must be **greater** than export timeout |
| Caddy `servers.timeouts.write` | **250s** | HTTP write deadline above PHP |
| FrankenPHP `max_wait_time` | **30s** | Caps how long a request may wait for a free PHP thread |

When raising `export.timeout` in YAML, raise PHP + Caddy write timeouts in the same change.

The main difference between development and production is:

| Aspect | Development (`classic`) | Default / production (`worker`) |
|--------|-------------------------|----------------------------------|
| `FRANKENPHP_MODE` | **`classic`** (set explicitly) | **`worker`** (default) |
| FrankenPHP worker mode | **Off** (one PHP process per request) | **On** (workers keep app in memory) |
| Twig cache | **Off** (`config/packages/dev/twig.yaml`) | **On** (default) |
| OPcache revalidation | Every request (`docker/php-dev.ini`) | Default (e.g. 2 seconds) |
| HTTP cache headers | `no-store`, `no-cache` (in Caddyfile.dev) | Omitted or cache-friendly |
| Symfony cache on startup | Cleared in Makefile before `up` | Not cleared (or warmup only) |
| `APP_ENV` / `APP_DEBUG` | `dev` / `1` | `prod` / `0` (or `dev` + worker for compatibility tests) |

**Ports:** Each demo uses `PORT` from its `.env`. To run multiple demos at once, set a different `PORT` per demo (e.g. 8007 for symfony7, 8008 for symfony8) as per the bundle standard protocol.

---

## What the demos include

The demo applications are configured for **local development and debugging**:

- **Symfony Web Profiler** and **Debug bundle** — enabled in `dev` and `test` environments.
- **Anonymize Bundle** (`Nowo\AnonymizeBundle\AnonymizeBundle`) — the bundle under test; enabled in the demos.

Example `config/bundles.php` (Symfony 8 demo):

```php
<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class        => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class         => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class                  => ['all' => true],
    Nowo\AnonymizeBundle\AnonymizeBundle::class                   => ['dev' => true, 'test' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class                => ['dev' => true, 'test' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class    => ['dev' => true, 'test' => true],
    // ...
];
```

In **production** (`APP_ENV=prod`), only bundles registered for `all` or `prod` are loaded.

---

## Development configuration

Goal: every change to PHP, Twig or config is visible on the next browser refresh without restarting the container. No long-lived PHP workers; cache disabled or revalidated on every request.

### 1. Caddyfile (development)

The development Caddyfile is **docker/frankenphp/Caddyfile.dev** in each demo. It uses plain `php_server` (no worker) and cache-busting headers. The entrypoint copies it over `/etc/frankenphp/Caddyfile` when **`FRANKENPHP_MODE=classic`** (set this in `.env` when you want classic instead of the default worker). Mount it in docker-compose so you can edit it without rebuilding.

### 2. PHP configuration (development)

The demos include **docker/php-dev.ini** with `opcache.revalidate_freq=0`. Mount it in docker-compose: `./docker/php-dev.ini:/usr/local/etc/php/conf.d/99-dev.ini:ro`.

### 3. Twig configuration (development)

The demos use **config/packages/dev/twig.yaml** with `twig.cache: false` so template changes are visible on refresh.

### 4. Docker Compose (development)

Each demo's **docker-compose.yml** passes `FRANKENPHP_MODE=${FRANKENPHP_MODE:-worker}` from the demo `.env` (template: `.env.example`, default **`worker`**), plus `APP_ENV=dev` and `APP_DEBUG=1`, and mounts the app, the bundle (`../..:/var/anonymize-bundle`), `docker/frankenphp/Caddyfile.dev`, and `docker/php-dev.ini`. The entrypoint applies classic/worker according to that variable. Existing DB env vars (DATABASE_URL, etc.) are kept.

### 5. Start the demo (development)

From the bundle root: `make -C demo/symfony8 up` (or symfony7). Or from the demo directory: `make up`.

---

## Production configuration

**`FRANKENPHP_MODE=worker`** is the default (worker Caddyfile). For a full production Symfony profile, also set `APP_ENV=prod` and `APP_DEBUG=0`, and do not mount `php-dev.ini`. See [TwigInspectorBundle DEMO-FRANKENPHP](https://github.com/nowo-tech/TwigInspectorBundle/blob/main/docs/DEMO-FRANKENPHP.md) for the full production Caddyfile and steps.

---

## Switching between classic and worker

- **Default / worker:** `FRANKENPHP_MODE=worker` (`.env.example` default). Entrypoint keeps the worker Caddyfile.
- **Classic (hot-reload friendly):** set `FRANKENPHP_MODE=classic` in `.env`. Entrypoint copies Caddyfile.dev (no worker, no-cache headers). Keep `APP_ENV=dev`, mount php-dev.ini, Twig cache off.

After changing `.env`, run `docker compose up -d` (or `make up`) so the container is **recreated** with the new env — **no image rebuild**. A plain `restart` does not reload environment variables.

---

## Reproducing in another bundle

See [TwigInspectorBundle DEMO-FRANKENPHP](https://github.com/nowo-tech/TwigInspectorBundle/blob/main/docs/DEMO-FRANKENPHP.md) section "Reproducing in another bundle" for the full checklist.

---

## Troubleshooting

- **Changes not visible:** Ensure `FRANKENPHP_MODE=classic` (Caddyfile.dev has no `worker`), add dev twig.yaml and php-dev.ini, restart container, hard-refresh browser.
- **Web Profiler not visible:** Check `APP_ENV=dev` and `APP_DEBUG=1`, and that WebProfilerBundle is enabled for `dev` in bundles.php.
- **Demo times out:** Check port is free, container logs (`docker-compose logs php`), and required env vars (e.g. APP_SECRET). For AnonymizeBundle demos, ensure DB services (MySQL, PostgreSQL, etc.) are healthy.
