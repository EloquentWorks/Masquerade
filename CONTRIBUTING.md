# 🤝 Contributing to Laravel Exile

Thank you for considering a contribution to Laravel Exile! Contributions of all sizes are appreciated, including bug reports, documentation improvements, additional tests, security hardening, and carefully designed features.

Please read the [Code of Conduct](CODE_OF_CONDUCT.md) before participating.

## 🧭 Ways to Contribute

You can help by:

- Reporting reproducible bugs
- Suggesting focused improvements
- Improving documentation and examples
- Adding regression and compatibility tests
- Improving static-analysis coverage
- Fixing defects
- Reviewing open pull requests
- Helping verify Laravel and PHP compatibility

## 🚨 Security Vulnerabilities

Do not report security vulnerabilities through public GitHub issues.

Follow the private reporting process in [SECURITY.md](SECURITY.md).

## 🐛 Reporting Bugs

Before opening a bug report:

1. Confirm the issue occurs on a currently supported version.
2. Search existing issues and pull requests.
3. Reduce the problem to the smallest reproducible example.
4. Run the package's quality suite when possible.

Include:

- Laravel Exile version
- Laravel version
- PHP version
- Database driver and version
- Queue driver, when notifications are involved
- Relevant configuration
- Steps to reproduce
- Expected behavior
- Actual behavior
- Exception message and stack trace
- A minimal reproduction or failing test

Remove secrets, private evidence, access tokens, real IP addresses, and other sensitive information before posting.

## 💡 Suggesting Features

Feature proposals should explain:

- The problem being solved
- Why the feature belongs in the core package
- The proposed public API
- Expected configuration or migrations
- Security and privacy considerations
- Backward-compatibility impact
- Alternatives considered

Large features should be discussed in an issue before implementation.

## 🛠️ Development Setup

Fork and clone the repository:

```bash
git clone https://github.com/<your-username>/Exile.git
cd Exile
```

Install dependencies:

```bash
composer install
```

Run the complete quality suite:

```bash
composer quality
```

Or run each tool separately:

```bash
composer format
composer analyse
composer test
```

Check formatting without changing files:

```bash
composer format:test
```

## 🌿 Branches

Create a focused branch from the latest default branch:

```bash
git checkout main
git pull --ff-only
git checkout -b fix/descriptive-name
```

Suggested prefixes:

```text
fix/
feature/
docs/
tests/
refactor/
chore/
```

Keep each branch limited to one clear purpose.

## 🧪 Tests

Every behavioral change should include PHPUnit coverage.

Tests should cover:

- The successful path
- Invalid input
- Authorization or security boundaries
- Temporary and permanent enforcement
- Transaction rollback where applicable
- Configuration variants
- Relevant database behavior
- Backward-compatibility expectations

Run a focused test:

```bash
vendor/bin/phpunit --filter CombinedBanBehaviorTest
```

Run the full suite before opening a pull request:

```bash
composer test
```

Do not delete or weaken an existing test merely to make a change pass.

## ✅ Static Analysis

Run PHPStan:

```bash
composer analyse
```

Prefer accurate native types and useful PHPDoc over broad suppressions.

For Eloquent relationships, preserve Larastan generics such as:

```php
/** @return MorphMany<Ban, $this> */
```

Avoid adding ignore rules unless the reported problem cannot be represented correctly in PHP or PHPDoc.

## 🎨 Code Style

Laravel Exile follows Laravel-style conventions and uses Laravel Pint.

Format the code:

```bash
composer format
```

General expectations:

- Use strict, descriptive method and variable names
- Prefer small, focused methods
- Use named arguments when they improve readability
- Keep public APIs consistent
- Avoid comments that only repeat the code
- Document security assumptions and surprising behavior
- Preserve backward compatibility unless a major release is planned

## 🗃️ Database Changes

Because stable versions may already be installed in user applications:

- Add a new migration instead of modifying a released migration
- Provide a complete `down()` method
- Use configurable package table names
- Test migration and rollback behavior
- Document upgrade steps in `UPGRADING.md`
- Mention schema changes in `CHANGELOG.md`

Consider SQLite, MySQL, and PostgreSQL differences.

## 🔐 Security-Sensitive Changes

Changes involving identifiers, evidence, notifications, middleware, transactions, pruning, or escalation require additional care.

Consider:

- Trusted proxy behavior
- Hash-key stability
- Sensitive-data disclosure
- Evidence authorization
- Queue and after-commit behavior
- Transaction boundaries
- Concurrency and duplicate enforcement
- Retention and destructive operations
- Constant-time comparisons where appropriate

## 📚 Documentation

Update documentation when a change affects:

- Installation
- Configuration
- Public methods
- Middleware
- Events or notifications
- Database schema
- Commands
- Security guidance
- Upgrade steps

Keep examples aligned with the actual method signatures and configuration structure.

Use relative links for repository documentation.

## 💾 Commits

Write clear, focused commit messages.

Examples:

```text
Fix strict combined-ban matching
Add evidence checksum verification tests
Document customizable notification templates
```

Avoid mixing unrelated formatting, refactoring, and behavioral changes in one commit.

## 🔀 Pull Requests

Before opening a pull request, confirm:

- [ ] The change has one clear purpose
- [ ] PHPUnit tests pass
- [ ] PHPStan passes
- [ ] Pint passes
- [ ] New behavior has tests
- [ ] Documentation is updated
- [ ] Database changes use new migrations
- [ ] `CHANGELOG.md` is updated when appropriate
- [ ] `UPGRADING.md` is updated when user action is required
- [ ] No secrets, debug files, or generated artifacts are committed

In the pull request description, explain:

- What changed
- Why it changed
- How it was tested
- Any migration or upgrade requirements
- Any backward-compatibility concerns

## 🧑‍⚖️ Review Process

Maintainers may request changes for:

- API consistency
- Missing tests
- Backward compatibility
- Security or privacy concerns
- Performance
- Documentation
- Scope

A pull request may be declined when the feature is too application-specific, duplicates Laravel functionality, or increases maintenance burden without sufficient benefit.

## 📄 License

By contributing, you agree that your contribution will be licensed under the repository's [MIT License](LICENSE).

Thank you for helping make Laravel Exile safer, clearer, and more useful. 🛡️
