# 🔐 Security Policy

Security is taken seriously. Thank you for helping keep this project and its users safe.

## 🚨 Reporting a Vulnerability

**Do not report security vulnerabilities through public GitHub issues, discussions, pull requests, or other public channels.**

Use the repository's private vulnerability-reporting feature when available:

1. Open the repository on GitHub.
2. Select **Security**.
3. Open **Advisories**.
4. Choose **Report a vulnerability**.

If private vulnerability reporting is not enabled, contact the repository or organization maintainer privately using an official contact method listed on the GitHub profile, organization page, or project documentation.

Do not include exploit details in a public issue while a vulnerability is unresolved.

## 📋 What to Include

A useful vulnerability report should include:

- A clear description of the issue
- The affected package or project version
- Laravel version
- PHP version
- Relevant database, cache, queue, or session driver
- Preconditions required to exploit the issue
- Step-by-step reproduction instructions
- A minimal proof of concept when safe to provide
- Expected behavior
- Actual security impact
- Whether authentication is required
- Whether user interaction is required
- Suggested remediation, if known

Please remove unrelated secrets, credentials, private customer data, and production information.

## 🛡️ Scope

Security issues may include, but are not limited to:

- Authentication bypass
- Authorization bypass
- Privilege escalation
- Insecure direct object references
- Cross-site scripting
- Cross-site request forgery
- SQL or command injection
- Unsafe deserialization
- Server-side request forgery
- Path traversal
- Arbitrary file access or upload
- Sensitive-data exposure
- Token or credential leakage
- Predictable security tokens
- Broken signature or webhook verification
- Replay attacks
- Session or cookie vulnerabilities
- Cryptographic misuse
- Mass-assignment vulnerabilities
- Unsafe impersonation behavior
- Race conditions with security impact
- Destructive-operation authorization failures
- Security-sensitive cache poisoning
- Unsafe default configuration

A bug is not necessarily a security vulnerability merely because it causes an error or unexpected behavior. Reports should describe a plausible security boundary that can be crossed or abused.

## 📦 Supported Versions

Security fixes are generally provided for versions that are actively maintained by the project.

Unless a repository states otherwise, users should:

- Run the latest stable release
- Use a supported Laravel version
- Use a supported PHP version
- Keep Composer dependencies up to date
- Review release notes before upgrading across breaking versions

Older releases may not receive security fixes.

When reporting an issue, indicate whether it also affects the latest stable version.

## 🤫 Coordinated Disclosure

Please allow maintainers a reasonable opportunity to investigate and resolve a vulnerability before public disclosure.

During the investigation:

- Keep vulnerability details private
- Avoid publishing proof-of-concept exploits
- Avoid testing against systems you do not own or have permission to test
- Avoid accessing, modifying, or retaining real user data
- Avoid denial-of-service testing against public infrastructure

Maintainers may ask for additional information, a reduced reproduction, or help validating a proposed fix.

## 🧭 Response Process

When a valid report is received, maintainers will generally:

1. Acknowledge the report.
2. Reproduce and assess the issue.
3. Determine affected versions and impact.
4. Develop and review a fix.
5. Add regression tests where practical.
6. Prepare upgrade or mitigation guidance.
7. Release a patched version when necessary.
8. Coordinate disclosure as appropriate.

Response times vary based on severity, complexity, maintainer availability, and the amount of information provided.

## 🧪 Security Testing

Security testing is welcome when performed responsibly.

Please:

- Test only systems, repositories, and environments you are authorized to test
- Prefer local test applications and isolated environments
- Use synthetic data
- Avoid persistence, destructive actions, and denial-of-service behavior
- Stop testing if you encounter real user data or secrets
- Report the issue privately

Do not attempt to demonstrate impact by compromising third-party applications that use the project.

## 🔑 Secrets and Credentials

Never commit or include real secrets in issues, pull requests, tests, fixtures, or examples.

This includes:

- API keys
- Access tokens
- Passwords
- Private keys
- Signing secrets
- Webhook secrets
- Encryption keys
- Production database credentials
- Session cookies
- OAuth credentials
- Real recovery codes

Use obvious dummy values in documentation and tests.

If a real secret is accidentally disclosed, rotate or revoke it immediately. Deleting it from Git history alone does not make the secret safe again.

## 🧩 Dependency Security

Projects should keep dependencies current and review security advisories that affect:

- Laravel
- Symfony components
- Composer packages
- JavaScript dependencies, when applicable
- Database drivers
- Authentication or cryptography libraries

A vulnerable transitive dependency may require an application-level mitigation even when the project itself does not directly contain the flaw.

## 🔒 Secure Configuration

Applications using these projects remain responsible for secure deployment and configuration.

Common recommendations include:

- Use HTTPS in production
- Keep `APP_DEBUG=false` in production
- Protect `.env` and other secret files
- Use strong application keys and rotate compromised credentials
- Configure trusted proxies correctly
- Validate webhook signatures
- Use secure cookie settings
- Apply CSRF protection where appropriate
- Rate-limit sensitive endpoints
- Authorize every sensitive operation
- Validate uploaded files and external URLs
- Restrict administrative functionality
- Avoid logging credentials or sensitive personal data
- Use database transactions where partial writes could create security problems

Package defaults should be secure, but host applications must still apply appropriate authentication, authorization, infrastructure, and operational controls.

## 🗄️ Data and Privacy

Security reports should minimize collection and disclosure of personal data.

Project code should avoid storing sensitive data unless necessary and documented.

Where applicable, consider:

- Data minimization
- Retention periods
- Secure deletion
- Encryption
- Access controls
- Audit logging
- Redaction
- Export and deletion workflows

Do not place secrets or unnecessary personal information in logs.

## 🔄 Token, Session, and Identifier Safety

When working with security-sensitive tokens or identifiers:

- Use cryptographically secure random values
- Store hashes instead of reusable secrets where practical
- Apply expiration and revocation
- Avoid exposing internal secrets through URLs or logs
- Use constant-time comparisons for secret values where appropriate
- Rotate credentials after suspected compromise
- Consider replay protection and idempotency
- Do not treat browser fingerprints, IP addresses, or User-Agent strings as strong authentication credentials

## 🌐 Webhooks and External Integrations

When a project accepts webhooks or signed callbacks:

- Verify signatures before trusting payloads
- Validate timestamps when supported
- Protect against replay attacks
- Make handlers idempotent
- Do not trust redirect or success URLs as proof of payment or completion
- Re-fetch authoritative state from the provider when appropriate
- Avoid logging provider secrets

## 🧱 Package Security Boundaries

Reusable Laravel packages should not assume that authentication alone implies authorization.

Packages should, where appropriate:

- Expose policy or authorization hooks
- Avoid trusting user-supplied ownership identifiers
- Avoid application-specific privilege assumptions
- Validate host-provided callbacks and resolvers
- Document operations that require step-up authentication
- Document security-sensitive configuration
- Avoid weakening Laravel's built-in security protections

## 📚 Security-Sensitive Documentation

Security-relevant behavior should be documented clearly, especially when it involves:

- Authentication
- Authorization
- Trusted devices
- API tokens
- Webhooks
- Payments
- File uploads
- Impersonation
- Moderation
- Destructive commands
- Pruning
- Encryption
- Queue processing
- External callbacks

Documentation should distinguish safe defaults from optional behavior that requires additional application-level protections.

## 📣 Public Disclosure

After a fix is available, maintainers may publish a security advisory describing:

- Affected versions
- Patched versions
- Severity
- Impact
- Mitigations
- Upgrade instructions

Reports may receive public credit when appropriate and when the reporter agrees.

## ❌ Out of Scope

The following are generally not considered project security vulnerabilities by themselves:

- Vulnerabilities that require intentionally insecure host-application configuration
- Unsupported PHP or Laravel versions
- Social engineering
- Missing security headers that belong to deployment infrastructure
- Denial-of-service claims without a practical project-specific attack
- Reports produced only by automated scanners without a reproducible security impact
- Issues that require already having equivalent administrative privileges

These may still be useful bug reports or hardening suggestions, but they should not be reported publicly if they contain potentially exploitable details.

## 📄 License

Security-related contributions are subject to the repository's [license](LICENSE).

Thank you for reporting vulnerabilities responsibly and helping protect the Laravel community. 🛡️
