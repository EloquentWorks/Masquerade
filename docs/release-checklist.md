# ✅ Release Checklist

Use this checklist when preparing a Laravel project or package release.

Copy it into a release issue or pull request when useful.

## 🧭 Release Planning

- [ ] Confirm the intended version number
- [ ] Confirm whether the release is patch, minor, major, or pre-release
- [ ] Review open release-blocking issues
- [ ] Review open pull requests intended for the release
- [ ] Confirm supported Laravel versions
- [ ] Confirm supported PHP versions
- [ ] Confirm supported database drivers
- [ ] Confirm the default branch is up to date

## 🧹 Repository State

- [ ] Pull the latest default branch
- [ ] Confirm the working tree is clean
- [ ] Remove debug code
- [ ] Remove temporary files
- [ ] Confirm no secrets or credentials are committed
- [ ] Confirm generated artifacts are intentional
- [ ] Confirm `.gitignore` is appropriate

Useful commands:

```bash
git checkout main
git pull --ff-only
git status
```

## 📦 Composer

- [ ] Run `composer install`
- [ ] Run `composer validate --strict`
- [ ] Confirm package metadata is correct
- [ ] Confirm package name is correct
- [ ] Confirm license metadata is correct
- [ ] Confirm autoload configuration is correct
- [ ] Confirm Laravel package discovery configuration is correct
- [ ] Confirm dependency constraints are intentional
- [ ] Confirm minimum PHP version is intentional
- [ ] Confirm minimum Laravel version is intentional

## 🎨 Formatting

- [ ] Run Laravel Pint
- [ ] Confirm formatting check passes
- [ ] Confirm no unrelated formatting changes remain

Example:

```bash
composer format:test
```

Or:

```bash
vendor/bin/pint --test
```

## ✅ Static Analysis

- [ ] Run PHPStan or Larastan
- [ ] Resolve new static-analysis errors
- [ ] Review new ignore rules carefully
- [ ] Confirm Eloquent relationship generics are correct where used
- [ ] Confirm public method types are accurate

Example:

```bash
composer analyse
```

## 🧪 Tests

- [ ] Run the complete test suite
- [ ] Confirm new features have tests
- [ ] Confirm bug fixes include regression tests
- [ ] Test invalid input paths
- [ ] Test authentication boundaries
- [ ] Test authorization boundaries
- [ ] Test configuration variants
- [ ] Test transactions and rollback where relevant
- [ ] Test events and notifications where relevant
- [ ] Test queue behavior where relevant
- [ ] Test concurrency-sensitive logic where relevant
- [ ] Confirm no tests were weakened merely to pass

Example:

```bash
composer test
```

## 🗃️ Database

If the release changes the database:

- [ ] Add new migrations instead of modifying released migrations
- [ ] Confirm migrations run from a clean install
- [ ] Confirm upgrade migrations run from the previous stable version
- [ ] Confirm rollback behavior
- [ ] Confirm indexes are intentional
- [ ] Confirm configurable table names are respected
- [ ] Review SQLite compatibility
- [ ] Review MySQL/MariaDB compatibility
- [ ] Review PostgreSQL compatibility
- [ ] Document destructive or long-running migrations

## ⚙️ Configuration

If configuration changes:

- [ ] Confirm new keys have safe defaults
- [ ] Confirm renamed keys are documented
- [ ] Confirm removed keys are documented
- [ ] Confirm new environment variables are documented
- [ ] Confirm published configuration matches documentation
- [ ] Confirm breaking configuration changes have upgrade guidance

## 🔐 Security

- [ ] Review authentication changes
- [ ] Review authorization changes
- [ ] Review token and secret handling
- [ ] Review cookies and sessions
- [ ] Review webhook verification
- [ ] Review payment verification where applicable
- [ ] Review file upload handling where applicable
- [ ] Review rate limiting where applicable
- [ ] Review sensitive logging
- [ ] Review transaction boundaries
- [ ] Review replay/idempotency protection
- [ ] Review pruning or destructive operations
- [ ] Confirm secrets are not exposed
- [ ] Confirm `SECURITY.md` is current

If this is a security release:

- [ ] Coordinate private disclosure
- [ ] Identify affected versions
- [ ] Identify patched versions
- [ ] Prepare mitigation guidance
- [ ] Prepare security advisory text

## 📚 Documentation

- [ ] Update `README.md`
- [ ] Update `SECURITY.md` if needed
- [ ] Update `CONTRIBUTING.md` if needed
- [ ] Update installation instructions
- [ ] Update configuration examples
- [ ] Update public API examples
- [ ] Update middleware documentation
- [ ] Update events/notifications documentation
- [ ] Update command documentation
- [ ] Update migration guidance
- [ ] Verify relative documentation links
- [ ] Confirm examples match actual method signatures

## 📝 GitHub Release Notes

- [ ] Prepare a concise release summary
- [ ] Document new features
- [ ] Document changed behavior
- [ ] Document important bug fixes
- [ ] Document deprecations when applicable
- [ ] Document removed behavior when applicable
- [ ] Document security fixes when appropriate
- [ ] Add upgrade notes when needed
- [ ] Avoid using a raw commit log as the release description

Suggested sections:

```text
What's New
Changed
Fixed
Security
Upgrade Notes
Breaking Changes
```

## ⚠️ Breaking Changes

If this is a breaking release:

- [ ] Document every known breaking change
- [ ] Add upgrade instructions
- [ ] Document removed APIs
- [ ] Document renamed APIs
- [ ] Document changed method signatures
- [ ] Document changed configuration keys
- [ ] Document changed event payloads
- [ ] Document database changes
- [ ] Document new Laravel/PHP minimums
- [ ] Confirm the major version is appropriate

## 🚦 CI

- [ ] Confirm GitHub Actions pass
- [ ] Confirm all supported PHP versions pass
- [ ] Confirm all supported Laravel versions pass
- [ ] Confirm database matrix jobs pass where configured
- [ ] Confirm formatting job passes
- [ ] Confirm static-analysis job passes
- [ ] Confirm test job passes
- [ ] Confirm Composer validation passes

## 🔍 Final Local Verification

- [ ] Run the full quality suite from the final release commit
- [ ] Confirm there are no uncommitted changes afterward
- [ ] Confirm the release commit is pushed
- [ ] Confirm CI passes on the exact release commit

Example:

```bash
composer quality
git status
```

## 🏷️ Tag

- [ ] Confirm the version number one final time
- [ ] Create an annotated tag
- [ ] Push the tag
- [ ] Confirm the tag appears on GitHub
- [ ] Confirm the tag points to the intended commit
- [ ] Do not move or reuse an already published tag

Example:

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

## 🚀 GitHub Release

- [ ] Create the GitHub Release from the tag
- [ ] Add the prepared release notes
- [ ] Highlight major features
- [ ] Highlight important fixes
- [ ] Include breaking changes
- [ ] Include upgrade instructions
- [ ] Include security notes where appropriate
- [ ] Link to relevant documentation
- [ ] Publish the release

## 📦 Packagist

For Composer packages:

- [ ] Confirm Packagist detects the new tag
- [ ] Confirm the new version appears correctly
- [ ] Confirm Composer can resolve the new version
- [ ] Confirm package metadata looks correct
- [ ] Confirm dependency constraints are correct

Example:

```bash
composer require vendor/package:^1.0
```

## 🧪 Post-Release Smoke Test

- [ ] Create or use a clean Laravel application
- [ ] Install the released package from Composer
- [ ] Confirm package auto-discovery works
- [ ] Publish configuration if applicable
- [ ] Publish migrations if applicable
- [ ] Run migrations
- [ ] Exercise the main documented workflow
- [ ] Confirm commands work
- [ ] Confirm routes/middleware work where applicable
- [ ] Confirm README installation instructions are accurate

## 📣 After Release

- [ ] Close completed milestone items
- [ ] Update project boards if used
- [ ] Merge or retarget pending documentation work
- [ ] Create follow-up issues for deferred work
- [ ] Monitor new bug reports
- [ ] Monitor installation or upgrade problems

## 🐛 If Something Went Wrong

- [ ] Do not rewrite the published tag
- [ ] Create a fix on the default branch
- [ ] Add a regression test
- [ ] Prepare new GitHub release notes
- [ ] Release a new patch version
- [ ] Follow `SECURITY.md` if the issue is security-sensitive

---

See [RELEASE.md](RELEASE.md) for the complete release process.
