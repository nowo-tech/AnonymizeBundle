# Branching Strategy

This project follows a simplified Git Flow workflow.

## Branch Types

| Branch | Purpose | Base | Merges to |
|--------|---------|------|-----------|
| `main` | Production releases only | - | - |
| `develop` | Development integration | `main` | `main` (releases) |
| `feature/*` | New features | `develop` | `develop` |
| `bugfix/*` | Bug fixes | `develop` | `develop` |
| `hotfix/*` | Urgent production fixes | `main` | `main` + `develop` |
| `release/*` | Release preparation | `develop` | `main` + `develop` |

## Workflow Diagram

```
main     ●─────────────────●─────────────────●  (v1.0.0)  (v1.1.0)
          \               /                 /
develop    ●─────●───●───●─────●───●───────●
                  \     /       \         /
feature/xxx        ●───●         \       /
                                  \     /
bugfix/yyy                         ●───●
```

## Creating Branches

### New Feature

```bash
git checkout develop
git pull origin develop
git checkout -b feature/my-feature
# ... work on feature ...
git push -u origin feature/my-feature
# Create Pull Request to develop
```

### Bug Fix

```bash
git checkout develop
git pull origin develop
git checkout -b bugfix/fix-description
# ... fix bug ...
git push -u origin bugfix/fix-description
# Create Pull Request to develop
```

### Hotfix (urgent production fix)

```bash
git checkout main
git pull origin main
git checkout -b hotfix/critical-fix
# ... fix issue ...
git push -u origin hotfix/critical-fix
# Create Pull Request to main AND develop
```

### Release

```bash
git checkout develop
git pull origin develop
git checkout -b release/1.2.0
# ... update version, changelog ...
git push -u origin release/1.2.0
# Create Pull Request to main
# After merge, tag the release and merge back to develop
```

## Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (X.0.0): Breaking changes
- **MINOR** (0.X.0): New features, backward compatible
- **PATCH** (0.0.X): Bug fixes, backward compatible

## Tagging Releases

After merging a release to `main`:

```bash
git checkout main
git pull origin main
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin v1.2.0
```

## Branch Protection Rules (GitHub)

Recommended settings for `main` branch:

- ✅ Require pull request before merging
- ✅ Require status checks to pass (CI)
- ✅ Require conversation resolution before merging
- ✅ Do not allow bypassing the above settings

## Commit Message Convention

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `style` | Code style (formatting, etc.) |
| `refactor` | Code refactoring |
| `test` | Adding/updating tests |
| `chore` | Maintenance tasks |

### Examples

```
feat(script): add support for Laravel version constraints
fix(installer): handle missing vendor directory
docs(readme): update installation instructions
chore(deps): update PHPUnit to v11
```

