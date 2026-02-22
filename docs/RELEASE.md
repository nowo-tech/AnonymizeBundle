# Release process

## Creating a new version (e.g. v1.0.14)

1. **Ensure everything is ready**
   - [CHANGELOG.md](CHANGELOG.md) has the target version (e.g. `[1.0.14]`) with date and full entry; `[Unreleased]` is at the top and empty or updated for the next cycle.
   - [UPGRADING.md](UPGRADING.md) has a section "Upgrading to X.Y.Z" with what's new, breaking changes (if any), and upgrade steps.
   - Tests pass: `make test` or `composer test`.
   - Code style: `make cs-check` or `composer cs-check`.

2. **Commit and push** any last changes to your default branch (e.g. `main` or `master`):
   ```bash
   git add -A
   git commit -m "Prepare v1.0.14 release"
   git push origin HEAD
   ```

3. **Create and push the tag**
   ```bash
   git tag -a v1.0.14 -m "Release 1.0.14

- Makefile: release-check target (cs-fix, cs-check, test-coverage, demo healthchecks)
- Demo Makefiles: release-verify, restart, build targets; help text updated
- PHP CS Fixer fixes [skip ci]
- Docs: RELEASE.md, SECURITY.md"
   git push origin v1.0.14
   ```

4. **GitHub Actions** (if configured) may create the GitHub Release from the tag.

5. **Packagist** (if the package is registered) will pick up the new tag; users can then `composer require nowo-tech/anonymize-bundle`.

## After releasing

- Keep `## [Unreleased]` at the top of [CHANGELOG.md](CHANGELOG.md) for the next version; add new changes there.
- Optionally bump a dev version in `composer.json` for development.
