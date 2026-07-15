# GitLab CI — requirements and configuration

This document describes the repository **CI requirements** and how to apply them on GitLab. The bundle publishes via GitHub (Actions + Packagist); if the project is mirrored or migrated to internal GitLab, these requirements must be replicated in the pipeline.

## CI requirements

### REQ-GIT-001 — History without Cursor co-author

Commit messages **must not** include Cursor agent trailers:

```text
Co-authored-by: Cursor <cursoragent@cursor.com>
```

or any variant containing `cursoragent@cursor.com`.

| Artifact | Location | Purpose |
|----------|----------|---------|
| Verification | `.scripts/check-no-cursor-coauthor.sh` | Fails if the ref history contains trailers |
| Cleanup | `.scripts/strip-cursor-coauthor-from-history.sh` | Rewrites messages and removes existing trailers |
| Preventive hook | `.githooks/commit-msg` | Strips trailers before creating the commit (`make setup-hooks`) |
| Makefile | `make check-no-cursor-coauthor` | Local shortcut and part of `make release-check` |
| Makefile | `make strip-cursor-coauthor-from-history` | Rewrites local `main` history (then `force-push`) |

#### Verify (local or CI job)

```bash
chmod +x .scripts/check-no-cursor-coauthor.sh
./.scripts/check-no-cursor-coauthor.sh HEAD
```

Equivalent:

```bash
make setup-hooks    # once per clone
make check-no-cursor-coauthor
```

On failure, the script lists affected commits.

#### Clean already-published history

When the check fails in CI (fresh clone from remote), **`git replace` does not help**: it only hides dirty commits on your machine and does not fix `origin`.

1. Ensure you have no uncommitted changes.
2. Run the rewrite on the main branch (default `main`):

```bash
chmod +x .scripts/strip-cursor-coauthor-from-history.sh
./.scripts/strip-cursor-coauthor-from-history.sh main
```

3. Verify again:

```bash
make check-no-cursor-coauthor
```

4. Publish the rewritten history (coordinate with the team):

```bash
git push --force-with-lease origin main
```

5. If release tags are affected, recreate them on the release commit and force-push the tag.

#### Example job in `.gitlab-ci.yml`

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

`GIT_DEPTH: "0"` is required: with a shallow clone the job does not see full history and may pass incorrectly.

#### Prevention

- Run `make setup-hooks` when cloning.
- Do not add `Co-authored-by: Cursor` manually to commit messages.
- Before release: `make release-check` (includes `check-no-cursor-coauthor`).

---

## Package Registry (optional)

If the bundle is published to the internal GitLab Package Registry (`https://gitlab.internal.nowo.tech`), follow the same pattern as other `nowo/bundles` bundles:

1. Configure `composer.json` with the group repository.
2. Add a `deploy` stage that calls the Composer API when a tag is created.
3. Document `auth.json` in the consuming project.

Minimal tag publish example:

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

## References

- [CONTRIBUTING.md](CONTRIBUTING.md) — hooks and contribution flow
- [RELEASE.md](RELEASE.md) — `check-no-cursor-coauthor` before release push
- [.github/workflows/ci.yml](../.github/workflows/ci.yml) — `git-hygiene` job on GitHub Actions
