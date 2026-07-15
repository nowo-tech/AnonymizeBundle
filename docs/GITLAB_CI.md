# GitLab CI — requisitos y configuración

Este documento recoge los **requisitos de CI** del repositorio y cómo aplicarlos en GitLab. El bundle publica en GitHub (Actions + Packagist); si el proyecto se espeja o migra a GitLab interno, estos requisitos deben replicarse en el pipeline.

## Requisitos de CI

### REQ-GIT-001 — Historial sin co-author de Cursor

Los mensajes de commit **no deben** incluir trailers del agente Cursor:

```text
Co-authored-by: Cursor <cursoragent@cursor.com>
```

ni variantes con `cursoragent@cursor.com`.

| Artefacto | Ubicación | Uso |
|-----------|-----------|-----|
| Verificación | `.scripts/check-no-cursor-coauthor.sh` | Falla si el historial del ref contiene trailers |
| Limpieza | `.scripts/strip-cursor-coauthor-from-history.sh` | Reescribe mensajes y elimina trailers ya presentes |
| Hook preventivo | `.githooks/commit-msg` | Quita trailers antes de crear el commit (`make setup-hooks`) |
| Makefile | `make check-no-cursor-coauthor` | Atajo local y en `make release-check` |
| Makefile | `make strip-cursor-coauthor-from-history` | Reescribe historial local de `main` (luego `force-push`) |

#### Verificar (local o job de CI)

```bash
chmod +x .scripts/check-no-cursor-coauthor.sh
./.scripts/check-no-cursor-coauthor.sh HEAD
```

Equivalente:

```bash
make setup-hooks    # una vez por clone
make check-no-cursor-coauthor
```

Si falla, el script lista los commits afectados.

#### Limpiar historial ya publicado

Cuando el check falla en CI (clone fresco del remoto), **`git replace` no sirve**: solo oculta commits sucios en tu máquina y no corrige `origin`.

1. Asegúrate de no tener cambios sin commitear.
2. Ejecuta la reescritura sobre la rama principal (por defecto `main`):

```bash
chmod +x .scripts/strip-cursor-coauthor-from-history.sh
./.scripts/strip-cursor-coauthor-from-history.sh main
```

3. Vuelve a comprobar:

```bash
make check-no-cursor-coauthor
```

4. Publica el historial reescrito (coordinar con el equipo):

```bash
git push --force-with-lease origin main
```

5. Si hay tags de release afectados, recréalos sobre el commit de release y haz force-push del tag.

#### Job de ejemplo en `.gitlab-ci.yml`

```yaml
stages:
  - validate
  - test

git-hygiene:
  stage: validate
  image: alpine/git:latest
  variables:
    GIT_DEPTH: "0"
  script:
    - chmod +x .scripts/check-no-cursor-coauthor.sh
    - ./.scripts/check-no-cursor-coauthor.sh HEAD
  rules:
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"
    - if: $CI_COMMIT_BRANCH == $CI_DEFAULT_BRANCH
```

`GIT_DEPTH: "0"` es obligatorio: con shallow clone el job no ve todo el historial y podría pasar por error.

#### Prevención

- Ejecuta `make setup-hooks` al clonar.
- No añadas manualmente `Co-authored-by: Cursor` en mensajes de commit.
- Antes de release: `make release-check` (incluye `check-no-cursor-coauthor`).

---

## Package Registry (opcional)

Si el bundle se publica en el Package Registry de GitLab interno (`https://gitlab.internal.nowo.tech`), sigue el mismo patrón que otros bundles de `nowo/bundles`:

1. Configura `composer.json` con el repositorio del grupo.
2. Añade un stage `deploy` que invoque la API de Composer al crear un tag.
3. Documenta `auth.json` en el proyecto consumidor.

Ejemplo mínimo de publicación por tag:

```yaml
deploy:
  stage: deploy
  script:
    - apk add --no-cache curl
    - >
      curl --fail-with-body
      --header "Job-Token: $CI_JOB_TOKEN"
      --data "tag=${CI_COMMIT_TAG}"
      "${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/packages/composer"
  rules:
    - if: $CI_COMMIT_TAG
```

---

## Referencias

- [CONTRIBUTING.md](CONTRIBUTING.md) — hooks y flujo de contribución
- [RELEASE.md](RELEASE.md) — `check-no-cursor-coauthor` antes del push de release
- [.github/workflows/ci.yml](../.github/workflows/ci.yml) — job `git-hygiene` en GitHub Actions
