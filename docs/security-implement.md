# Security Implementation Checklist for Church Platform

This document lists actionable security controls, implementation notes, and checklist items tailored for the Church Platform repository (Laravel 11). Use this as a roadmap for hardening the app and plugins.

## Goals
- Protect user data and secrets
- Ensure admin and plugin actions are access-controlled
- Limit abuse (rate limiting, captcha, throttling)
- Improve observability and incident response
- Ensure safe plugin lifecycle and isolation

## Secrets & Configuration
- Store secrets in environment variables (.env) or a secrets manager (AWS Secrets Manager, HashiCorp Vault). Avoid plaintext secrets in the DB unless encrypted.
- If secrets must live in DB: encrypt before saving and decrypt at runtime with application key. Restrict read access to the settings rows.
- Ensure .env.example contains placeholders only. Add a CI check to reject pushed secrets (pre-commit or GitHub Action scanning).

Implementation notes:
- Use Laravel's encryption (encrypt()/decrypt()) or a KMS-backed solution.
- Add a git-secrets or TruffleHog scan in CI.

## Authentication & Authorization
- Always attach explicit middleware on admin route groups. Example:

    Route::prefix('admin')->middleware(['auth:sanctum','can:admin'])->group(...);

- Use roles/permissions via spatie/laravel-permission. Test authorization in automated tests.
- Enforce multi-factor auth for admin users where appropriate (pragmarx/google2fa-laravel is included).

## Input Validation & Uploads
- Validate and sanitize all user input (Controllers, FormRequests). Fail-safe on invalid data.
- For file uploads (Spatie medialibrary): validate mime-types, size limits, virus scanning (ClamAV or hosted scanning) and store on object storage (S3/MinIO) with restricted ACLs.

## Rate Limiting & Abuse Protection
- Apply Laravel throttle middleware on public endpoints (SDUI endpoints, public church listing, auth endpoints, captcha endpoints).
- Protect API endpoints with API tokens + per-user rate limits; use Redis-backed rate limiter for consistency across nodes.

## Captcha & Bot Protection
- Keep Turnstile secret out of public responses. Verify tokens server-side using VerifyCaptcha middleware.
- Add rate-limiting and failure logging for repeated captcha failures (monitor for brute force attempts).

## Plugin Security & Lifecycle
- Define a plugin contract (interface) and require a ServiceProvider per plugin.
- Maintain a central config/plugins.php listing enabled plugins.
- Enforce migration/namespace/table naming conventions and prefix plugin DB objects where practical.
- Introduce plugin sandboxing rules: limit direct DB access, prefer calling core interfaces.

## Data Protection & Privacy
- Hash or pseudonymize PII where possible (TrackPageView stores ip_hash which is good). Avoid storing raw IPs or sensitive identifiers.
- Implement retention policy and automated purge jobs for analytics logs and session data.
- Add an endpoint/process to export and delete user data to comply with requests.

## Caching & Session Safety
- Use Redis (or equivalent) for cache and session stores in production. Ensure encryption of session cookies, set secure cookie flags (Secure, HttpOnly, SameSite).
- For SettingsManager cache invalidation, utilize Cache::tags and ensure cache store supports tags (Redis) to avoid fallback issues.

## Observability & Auditing
- Log authentication events, admin changes, plugin installs/uninstalls, and failed security checks.
- Send critical logs to external provider (Sentry, Datadog). Ensure logs do not contain secrets.
- Maintain audit table for admin actions and data-modifying operations.

## CI/CD & Tests
- Add CI checks: linting, phpunit/pest tests, static analysis (PHPStan/Psalm), secret scanning, dependency vulnerability scans (e.g., GitHub Dependabot).
- Add contract tests for API v1 (SDUI response schema) to detect breaking changes.

## Deployment & Infrastructure
- Use docker-compose/Kubernetes with secrets injected via environment or secrets manager.
- Ensure TLS termination at edge, HSTS headers, CSP header defaults for templates, and secure headers via middleware.

## Quick Developer Checklist (pre-merge)
- [ ] No secrets committed
- [ ] Tests pass (unit + integration)
- [ ] Route group protection verified for admin endpoints
- [ ] Upload validation added for file endpoints
- [ ] Rate limits applied to public endpoints
- [ ] Plugin changes documented and added to config/plugins.php

## Example quick fixes (code snippets)
- Add admin middleware in routes/api.php:

    Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum','can:admin'])->group(function () {
        // existing routes
    });

- Queue analytics writes (instead of sync DB writes) in app/Http/Middleware/TrackPageView.php:

    // dispatch job instead of direct create
    TrackPageViewJob::dispatch([...])->onQueue('analytics');

## Next steps
1. Add docs/security-implement.md to repository (this file).
2. Add CI secret scanner and set up Dependabot.
3. Implement queued analytics and enforce admin middleware.
4. Run a security review and produce a prioritized remediation PR list.

-----

Created by automated assistant; review and adjust for your organization policies.