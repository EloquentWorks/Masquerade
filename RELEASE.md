# 🚀 Releasing Laravel Exile

Laravel Exile follows Semantic Versioning.

## 🧭 Prepare

1. Complete [the release checklist](docs/release-checklist.md).
2. Update `CHANGELOG.md`.
3. Update `UPGRADING.md` when users must take action.
4. Prepare the GitHub release notes.
5. Confirm the working tree is clean.

## ✅ Validate

```bash
composer validate --strict
composer quality
```

Test the production dependency set:

```bash
composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader
```

Smoke-test supported Laravel versions in clean applications.

## 💾 Commit

```bash
git add .
git commit -m "Prepare v1.1.0 release"
git push
```

## 🏷️ Tag

```bash
git tag -a v1.1.0 -m "Laravel Exile v1.1.0"
git push origin v1.1.0
```

Never move a published stable tag. Create a new version when a released tag needs correction.

## 📣 GitHub Release

Create a release from `v1.1.0` and use `RELEASE_NOTES_v1.1.0.md`.

Suggested title:

```text
v1.1.0 — Enforcement Hardening and Custom Notifications
```

## 📦 Package Registry

Confirm the package registry receives the tag and resolves:

```bash
composer show eloquent-works/exile --all
```

## 🧪 Post-Release Smoke Test

```bash
composer create-project laravel/laravel exile-smoke
cd exile-smoke
composer require eloquent-works/exile:^1.1
php artisan exile:install --migrate --views
```

Verify middleware, one enforcement action, one queued notification, one evidence checksum, and one escalation.
