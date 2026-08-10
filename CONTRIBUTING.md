# 🤝 Contributing

Thank you for considering a contribution! Contributions of all sizes are welcome, including bug reports, documentation improvements, additional tests, security hardening, refactoring, and carefully designed features.

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
- Helping verify Laravel, PHP, and database compatibility

## 🚨 Security Vulnerabilities

Do not report security vulnerabilities through public GitHub issues, discussions, or pull requests.

Please follow the private reporting process described in [SECURITY.md](SECURITY.md).

## 🐛 Reporting Bugs

Before opening a bug report:

1. Confirm the issue occurs on a currently supported version.
2. Search existing issues and pull requests for duplicates.
3. Reduce the problem to the smallest reproducible example.
4. Run the project's quality suite when possible.

Include, when relevant:

- Package or project version
- Laravel version
- PHP version
- Database driver and version
- Cache driver
- Queue driver
- Session driver
- Relevant configuration
- Steps to reproduce
- Expected behavior
- Actual behavior
- Exception message and stack trace
- A minimal reproduction or failing test

Remove secrets, credentials, access tokens, private data, real IP addresses, API keys, and other sensitive information before posting.

## 💡 Suggesting Features

Feature proposals should explain:

- The problem being solved
- Why the feature belongs in the project
- The proposed public API
- Expected configuration changes
- Expected migrations or schema changes
- Security and privacy considerations
- Performance considerations
- Backward-compatibility impact
- Alternatives considered

Large or potentially breaking features should be discussed in an issue before implementation.

## 🛠️ Development Setup

Fork and clone the repository:

```bash
git clone https://github.com/<your-username>/<repository>.git
cd <repository>
```

Install dependencies:

```bash
composer install
```

If the repository provides a complete quality script, run:

```bash
composer quality
```

Common individual commands include:

```bash
composer format
composer analyse
composer test
```

To check formatting without modifying files, a repository may provide:

```bash
composer format:test
```

Always check `composer.json` for the exact scripts supported by the project.

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

Behavioral changes should include automated test coverage.

Tests should consider, when relevant:

- The successful path
- Invalid input
- Validation failures
- Authorization boundaries
- Authentication boundaries
- Security-sensitive behavior
- Configuration variants
- Database behavior
- Transactions and rollback
- Queued behavior
- Events and notifications
- Concurrency-sensitive behavior
- Backward-compatibility expectations

Run a focused test when useful:

```bash
vendor/bin/phpunit --filter ExampleTest
```

Run the full suite before opening a pull request:

```bash
composer test
```

Do not delete, disable, or weaken an existing test merely to make a change pass.

## ✅ Static Analysis

If the project uses PHPStan or Larastan, run:

```bash
composer analyse
```

Prefer accurate native types and useful PHPDoc over broad suppressions.

For Eloquent relationships, preserve appropriate Larastan generic annotations where the project uses them, for example:

```php
/** @return MorphMany<RelatedModel, $this> */
```

Avoid adding ignore rules unless the reported issue cannot be represented correctly through PHP types or PHPDoc.

## 🎨 Code Style

Projects should follow Laravel-style conventions and use Laravel Pint when configured.

Format code with:

```bash
composer format
```

General expectations:

- Use descriptive class, method, and variable names
- Prefer small, focused methods
- Prefer early returns when they improve readability
- Use named arguments when they improve clarity
- Keep public APIs consistent
- Follow existing project architecture
- Avoid unnecessary abstractions
- Avoid comments that merely repeat the code
- Document security assumptions and surprising behavior
- Preserve backward compatibility unless a breaking release is planned
- Keep formatting compatible with Laravel Pint

## 🧱 Architecture

Before introducing a new abstraction, review the existing project structure.

Prefer:

- Focused actions or services for business operations
- Form Requests for HTTP validation
- API Resources for response transformation
- Policies or gates for authorization
- Events for meaningful domain changes
- Jobs for work that belongs on a queue
- Value objects or DTOs when they improve correctness
- Configuration over hard-coded application assumptions

Avoid moving application-specific policy into a reusable package unless the behavior is intentionally part of its public contract.

## 🗃️ Database Changes

For published or stable releases:

- Add a new migration instead of modifying a previously released migration
- Provide a complete `down()` method where practical
- Respect configurable package table names when supported
- Add indexes intentionally
- Avoid destructive schema changes without an upgrade path
- Test migration and rollback behavior
- Consider SQLite, MySQL/MariaDB, and PostgreSQL differences

For reusable packages, avoid assumptions about the host application's user table, primary-key type, or authentication model unless explicitly documented.

## 🔐 Security-Sensitive Changes

Changes involving authentication, authorization, tokens, identifiers, secrets, middleware, cookies, sessions, uploads, webhooks, cryptography, transactions, pruning, impersonation, moderation, or destructive operations require additional care.

Consider:

- Authentication and authorization boundaries
- CSRF protection
- Rate limiting
- Trusted proxy behavior
- Secret and key stability
- Constant-time comparisons where appropriate
- Sensitive-data disclosure
- Mass-assignment behavior
- Injection risks
- File-path and upload validation
- Queue and after-commit behavior
- Transaction boundaries
- Concurrency and duplicate operations
- Replay and idempotency concerns
- Retention and destructive operations
- Logging of secrets or personal data
- Safe defaults

Security-sensitive pull requests should explain the threat model or abuse case being addressed.

## ⚡ Performance

Avoid unnecessary queries, repeated network calls, unbounded collections, or work inside frequently executed middleware.

When changing performance-sensitive code, consider:

- Query count
- Eager loading
- Index usage
- Cache behavior
- Queue suitability
- Batch operations
- Memory usage
- Large datasets
- Repeated model events
- N+1 queries

Include benchmarks or query-count comparisons when they materially support the change.

## 📚 Documentation

Update documentation whenever a change affects:

- Installation
- Requirements
- Configuration
- Environment variables
- Public methods
- Models or relationships
- Middleware
- Routes
- Events
- Notifications
- Commands
- Database schema
- Security guidance
- Upgrade steps
- Supported Laravel or PHP versions

Keep examples aligned with actual method signatures and configuration structure.

Use relative links for repository documentation.

## 💾 Commits

Write clear, focused commit messages.

Examples:

```text
Fix token rotation after expiration
Add PostgreSQL compatibility tests
Document configurable table names
```

Avoid mixing unrelated formatting, refactoring, documentation, and behavioral changes in one commit.

## 🔀 Pull Requests

Before opening a pull request, confirm:

- [ ] The change has one clear purpose
- [ ] Tests pass
- [ ] Static analysis passes, when configured
- [ ] Pint or formatting checks pass, when configured
- [ ] New behavior has appropriate tests
- [ ] Documentation is updated
- [ ] Database changes use safe migration practices
- [ ] Backward compatibility has been considered
- [ ] Security and privacy implications have been considered
- [ ] No secrets, debug files, credentials, or generated artifacts are committed

In the pull request description, explain:

- What changed
- Why it changed
- How it was tested
- Any migration or upgrade requirements
- Any backward-compatibility concerns
- Any security or performance considerations

## 🧑‍⚖️ Review Process

Maintainers may request changes for:

- API consistency
- Missing tests
- Architecture
- Backward compatibility
- Security or privacy concerns
- Performance
- Documentation
- Scope
- Long-term maintenance cost

A pull request may be declined when it is too application-specific, duplicates functionality already provided by Laravel or a project dependency, introduces unnecessary complexity, or increases maintenance burden without enough benefit.

## 📦 Releases and Backward Compatibility

Public APIs should remain backward compatible within the project's stated versioning policy.

Breaking changes should:

- Be intentional
- Be documented clearly
- Include upgrade guidance
- Be released according to the project's versioning strategy

Avoid silently changing configuration keys, method signatures, event payloads, database semantics, or other documented behavior.

## 📄 License

By contributing, you agree that your contribution will be licensed under the repository's [MIT License](LICENSE) or the license otherwise specified by the project.

Thank you for helping make this project safer, clearer, and more useful. ❤️
