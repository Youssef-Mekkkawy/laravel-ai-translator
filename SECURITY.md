# Security Policy

## Supported Versions

| Version | Supported |
|---|---|
| 1.x | ✅ Yes |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Email: **your-email@example.com**

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

You will receive a response within **48 hours**. If the issue is confirmed, a patch will be released as quickly as possible.

## Scope

Security issues we care about:

- **API key exposure** — keys appearing in logs, error messages, or translated output
- **Path traversal** — when reading/writing language files
- **Code injection** — via translation keys or values
- **Unauthorized dashboard access** — `/ai-translator` route protection

## Out of Scope

- Issues requiring physical access to the machine
- Social engineering
- Issues in third-party AI providers (report those directly to the provider)
