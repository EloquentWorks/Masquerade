# 🚀 Release Guide

This document describes the standard release process for this Laravel project.

The goal is to make releases predictable, reproducible, well-tested, and easy for users to understand and upgrade.

## 📌 Versioning

Follow [Semantic Versioning](https://semver.org/) unless the repository documents a different policy.

Use versions in the form:

```text
MAJOR.MINOR.PATCH
```

Examples:

```text
1.0.0
1.4.2
2.0.0
```

Use:

- **PATCH** for backward-compatible bug fixes
- **MINOR** for backward-compatible features
- **MAJOR** for breaking changes

Pre-release versions may use identifiers such as:

```text
1.0.0-beta.1
1.0.0-rc.1
```

## 🧭 Before Starting a Release

Before preparing a release:

1. Confirm the default branch is up to date.
2. Review open pull requests that may need to be included.
3. Review open issues that may block the release.
4. Confirm the intended version number.
5. Review upgrade documentation when applicable.
6. Confirm supported Laravel and PHP versions.
7. Run the full quality suite.

Typical commands:

```bash
composer install
composer quality
```

Or run the available checks separately:

```bash
composer format:test
composer analyse
composer test
```

Check `composer.json` for the exact scripts supported by the repository.

## 🧪 Quality Checks

A release should not be created until the project's required checks pass.

Verify, where applicable:

- PHPUnit tests pass
- Laravel Pint passes
- PHPStan or Larastan passes
- Composer validation passes
- Package discovery works
- Migrations run successfully
- Migrations roll back successfully
- Commands execute successfully
- Configuration publishes correctly
- Package routes load correctly
- Middleware aliases register correctly
- Events and notifications behave as documented
- Queued behavior works correctly
- Supported database drivers behave as expected
- Supported Laravel versions pass CI
- Supported PHP versions pass CI

Useful commands may include:

```bash
composer validate --strict
vendor/bin/pint --test
vendor/bin/phpstan analyse
vendor/bin/phpunit
```

## 🗃️ Database Changes

If the release includes database changes:

- Do not modify migrations that have already shipped in a stable release
- Add a new migration for schema changes
- Provide a working `down()` method where practical
- Verify configurable table names are respected
- Test installation from a clean database
- Test upgrading from the previous stable release
- Test rollback behavior
- Consider SQLite, MySQL/MariaDB, and PostgreSQL differences

Document any required migration commands in the README, upgrade guide, or GitHub release notes.

## ⚙️ Configuration Changes

If configuration changes:

- Add new keys with safe defaults
- Avoid silently renaming or removing existing keys
- Document new environment variables
- Document changed defaults
- Explain upgrade steps for breaking configuration changes
- Verify published configuration matches the documented structure

For removed or renamed configuration keys, provide a clear upgrade path.

## 🔐 Security Review

Security-sensitive releases deserve extra review.

Consider whether the release affects:

- Authentication
- Authorization
- Cookies
- Sessions
- Tokens
- Encryption
- Webhooks
- Payments
- File uploads
- Impersonation
- Moderation
- Trusted devices
- Rate limiting
- Queue processing
- Pruning
- Destructive commands
- External integrations

Confirm that:

- Secrets are not logged
- Authorization is enforced
- Security-sensitive comparisons are safe
- Replay and idempotency concerns are handled
- Transactions cover critical state changes
- Security documentation is current

If the release fixes a vulnerability, coordinate disclosure according to [SECURITY.md](SECURITY.md).

## 📚 Documentation Review

Before release, verify that documentation reflects the code.

Review:

- `README.md`
- `SECURITY.md`
- `CONTRIBUTING.md`
- `UPGRADING.md`, when present
- Installation instructions
- Configuration examples
- Public API examples
- Middleware documentation
- Events and notifications
- Commands
- Database schema guidance
- Security guidance

Examples should match actual method signatures and configuration keys.

## 📝 Release Notes

This project does not require a changelog.

Instead, document each release in the **GitHub Release notes** for its tag.

Release notes should explain meaningful user-facing changes and may use sections such as:

```text
What's New
Changed
Fixed
Security
Upgrade Notes
Breaking Changes
```

Example:

```markdown
## What's New

- Added device naming support.
- Added configurable expiration policies.

## Changed

- Improved trusted-device token rotation.

## Fixed

- Fixed revocation when multiple sessions are active.
```

Avoid using a raw commit log as release notes. Focus on changes that matter to users.

## ⚠️ Breaking Changes

Breaking changes require special care.

Before a breaking release:

- Document every known breaking change
- Add upgrade instructions
- Explain removed or renamed APIs
- Explain migration changes
- Explain configuration changes
- Explain event or payload changes
- Explain new minimum Laravel/PHP requirements
- Consider deprecating behavior before removal when practical

Breaking changes should normally be released in a new major version.

Upgrade guidance may live in:

- `UPGRADING.md`
- The README
- Dedicated documentation
- GitHub release notes

## 🔢 Update the Version

If the project stores its version in code or metadata, update it before tagging.

Do not add a version constant unless the project actually needs one.

Composer packages usually use Git tags as the package version source.

## 🧹 Repository Cleanup

Before creating the release, confirm the repository does not contain:

- Debug statements
- Temporary files
- Local environment files
- Credentials
- API keys
- Build artifacts that should not be committed
- IDE configuration that is intentionally ignored
- Accidentally generated files
- Uncommitted changes

Check:

```bash
git status
```

The working tree should be clean.

## ✅ Final Verification

Run the full release verification again from the final commit:

```bash
composer install
composer quality
```

If the project uses separate scripts:

```bash
composer format:test
composer analyse
composer test
```

Confirm CI passes on the release commit.

## 🏷️ Create the Git Tag

Create an annotated tag:

```bash
git tag -a v1.4.0 -m "Release v1.4.0"
```

Push it:

```bash
git push origin v1.4.0
```

If the repository does not use a `v` prefix, follow its existing tag convention.

Never reuse or silently move a published release tag.

## 📦 GitHub Release

Create a GitHub Release from the new tag.

The GitHub Release acts as the project's version history and should include:

- Version number
- Short summary
- Major features
- Important fixes
- Breaking changes
- Upgrade instructions
- Security notes when applicable

Avoid pasting a raw commit log as release notes.

## 📚 Packagist

For packages published on Packagist:

1. Confirm the repository is connected to Packagist.
2. Confirm the new Git tag is visible.
3. Confirm Packagist detects the release.
4. Verify package metadata.
5. Verify installation using the released constraint.

Example:

```bash
composer require vendor/package:^1.4
```

If Packagist does not update automatically, trigger an update using the repository's configured Packagist integration.

## 🧪 Post-Release Verification

After publishing:

- Install the released package in a clean Laravel application
- Confirm Composer resolves the expected version
- Run published migrations
- Publish configuration when applicable
- Exercise the primary documented workflow
- Verify links in the README and release notes
- Verify GitHub and Packagist show the correct version

Example:

```bash
composer create-project laravel/laravel release-test
cd release-test
composer require vendor/package:^1.4
```

## 🐛 If a Release Has a Problem

Do not rewrite an already published tag.

If a release contains a defect:

1. Fix the issue on the default branch.
2. Add a regression test.
3. Prepare new GitHub release notes.
4. Create a new patch release.

Example:

```text
1.4.0 -> 1.4.1
```

If the issue is security-sensitive, follow the private process in [SECURITY.md](SECURITY.md).

## 🔄 Release Automation

Repositories may automate parts of this process through GitHub Actions.

Automation may safely handle:

- Running tests
- Running static analysis
- Checking formatting
- Composer validation
- Building release artifacts
- Creating draft release notes
- Publishing documentation

Release automation should not bypass required review or quality checks.

## 📋 Release Checklist

Use [release-checklist.md](docs/release-checklist.md) for the practical step-by-step checklist used during each release.

## 📄 License

Releases remain subject to the repository's license.
