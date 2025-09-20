# Two-Factor Authentication (Email on New Device)

## Overview

The application implements a lightweight second factor mode: `email_on_new_device`. When enabled by a user, any login (or existing session re-use after session expiration) from a browser/device combination that is not yet trusted triggers an email challenge with a short‑lived verification code. After successful verification the browser becomes a _trusted device_ and future logins skip the challenge until the trust is revoked or record expires (if TTL policy added in future).

This design gives strong protection against password compromise without requiring an authenticator app (lower friction for casual users) while still allowing future expansion to TOTP, WebAuthn, or recovery codes.

## Data Model

### Users Table Additions

- `two_factor_mode` (string) — `disabled` | `email_on_new_device`.
- `two_factor_email_enabled_at` (timestamp nullable) — auditing when user last enabled.

### Trusted Devices: `user_trusted_devices`

| Column                                   | Purpose                                                               |
| ---------------------------------------- | --------------------------------------------------------------------- | ------------- | --------- |
| id                                       | PK                                                                    |
| user_id                                  | Belongs to user (cascade delete)                                      |
| fingerprint_hash                         | SHA-256 hash of (device_id                                            | normalized UA | platform) |
| device_label                             | User-assigned friendly name                                           |
| user_agent                               | Raw (truncated) user agent captured on trust/usage                    |
| ip_address                               | Last observed IP (blurred in UI; reveal-on-click)                     |
| client_name / client_os / client_browser | (Reserved / parsed heuristics)                                        |
| token_hash                               | SHA-256 of a random 64-char trust token (plain stored only in cookie) |
| last_used_at                             | Timestamp updated on each successful validation                       |
| revoked_at                               | Soft revoke timestamp                                                 |
| created_at / updated_at                  | Standard                                                              |

### Email Challenges: `email_second_factor_challenges`

| Column                  | Purpose                            |
| ----------------------- | ---------------------------------- |
| id                      | PK                                 |
| user_id                 | Owner                              |
| fingerprint_hash        | Match device being challenged      |
| code_hash               | SHA-256 of uppercase trimmed code  |
| attempts_remaining      | Decrement on failure; block when 0 |
| expires_at              | Expiration (TTL from config)       |
| consumed_at             | Set on successful verification     |
| created_at / updated_at | Standard                           |

## Fingerprint Strategy

Fingerprint = SHA-256(device_id | normalized_user_agent | platform_header). The `device_id` is a random 32-hex cookie (1 year). Platform header may use `Sec-CH-UA-Platform` if available; otherwise blank. Normalization trims and lowercases to reduce variability.

This avoids storing raw unbounded user agent in the fingerprint itself while keeping reasonably stable identity. If UA or platform changes significantly user will re-authorize (acceptable tradeoff).

## Challenge Flow

1. Authenticated request hits `EnsureSecondFactor` middleware.
2. If user not in `email_on_new_device` mode -> pass through.
3. Build fingerprint. If trusted device cookie present and matches (`token_hash` & fingerprint) -> update `last_used_at`, allow.
4. Else: `needsChallenge()`: no active trusted record -> issue new challenge:
    - Purge existing pending for same user+fingerprint.
    - Generate code (length & alphabet from config: `twofactor.code_length`).
    - Store challenge (hashed code, TTL, attempts).
    - Send email (queue or immediate fallback depending on `twofactor.use_queue`).
    - Store fingerprint in session + `url.intended` for redirect after success.
5. Redirect to challenge page.
6. User submits code:
    - Validate: exists, not expired, attempts_remaining > 0, hash match.
    - On success: mark consumed, optionally `trust device` (default true) -> create trusted device record & set cookie with plain trust token.
    - Forget session fingerprint; redirect to originally intended URL or dashboard fallback.
7. On failure: decrement attempts; when attempts=0 require re-login (future improvement) or allow resend.

## Trust Token

- Random 64 char (base62-like) stored only hashed.
- Cookie: `trusted_device_token` (httpOnly true in middleware usage; currently set with default flags; can harden with `secure`, `sameSite='lax'`).
- Validation: match user_id + fingerprint + token_hash not revoked.
- Updates IP + user_agent & `last_used_at` on each validation.

## Security Considerations

| Aspect             | Mitigation                                                                                 |
| ------------------ | ------------------------------------------------------------------------------------------ |
| Code Bruteforce    | Limited attempts + short TTL + mixed alphanumeric (no ambiguous chars).                    |
| Email Interception | Trust limited to fingerprint; revoke quickly via UI. Encourage strong mailbox security.    |
| Token Theft        | Token bound to fingerprint; stolen token from different UA+platform fails. HTTPS required. |
| Replay             | Single active challenge per fingerprint (older purged). Consumed marked on success.        |
| Enumeration        | Challenge page only reachable after auth & session fingerprint set.                        |
| Logging            | Minimal — logs mask fingerprint (prefix only) in debug mode.                               |
| PII Minimization   | IP blurred by default in UI; reveal ephemeral.                                             |

## Config (`config/twofactor.php`)

| Key                     | Default | Description                                |
| ----------------------- | ------- | ------------------------------------------ |
| code_length             | 6       | Characters in generated code               |
| code_ttl                | 300     | Lifetime (seconds)                         |
| max_attempts            | 5       | Attempts before lockout of challenge       |
| use_queue               | false   | If true mail queued; else immediate send() |
| trusted_device_ttl_days | null    | (Future) TTL for revoking stale devices    |

## Public API (Routes)

| Method | Route                               | Purpose                               |
| ------ | ----------------------------------- | ------------------------------------- |
| GET    | /2fa/challenge                      | Show challenge page                   |
| POST   | /2fa/verify                         | Verify code, trust device optionally  |
| POST   | /2fa/resend                         | Re-issue code (should add rate-limit) |
| GET    | /settings/security                  | Security dashboard                    |
| POST   | /settings/security/2fa/enable       | Enable mode (password required)       |
| DELETE | /settings/security/2fa/disable      | Disable mode (password required)      |
| PATCH  | /settings/security/devices/{device} | Rename trusted device                 |
| DELETE | /settings/security/devices/{device} | Revoke single device                  |
| DELETE | /settings/security/devices          | Revoke all devices                    |
| POST   | /settings/security/logout-others    | Logout other sessions                 |

## Blade Email (`resources/views/emails/twofactor/code.blade.php`)

Features: accessible markup, dark mode support, code emphasis, TTL display, anti-phishing reminder. Keep consistent brand tone with other transactional mails.

## Front-End Components

- Challenge Page (Vue): Pin input auto-submit, trust checkbox default on, resend action (consider adding cooldown/disabled state after click).
- Security Page: 2FA status badge, enable/disable modal (password), trusted devices table (rename, revoke, IP reveal), logout others.

## Extensibility Roadmap

| Feature           | Notes                                                             |
| ----------------- | ----------------------------------------------------------------- |
| TOTP (RFC 6238)   | Add shared secret, QR provisioning, fallback to email as backup.  |
| WebAuthn          | Strong phishing-resistant factor; store credentials per user.     |
| Recovery Codes    | One-time use codes; generate on enabling 2FA.                     |
| Device TTL        | Auto-expire trusted devices after N days; background cleanup job. |
| Resend Throttle   | Rate limit resend endpoint (e.g. 1 per 30s).                      |
| Audit Log         | Record enable/disable, challenge failures, revocations.           |
| Admin Enforcement | Policy to require 2FA for certain roles/groups.                   |
| Geo/ASN Display   | Light enrichment for unusual login awareness (ensure privacy).    |

## Operational Notes

- If queue worker not running and `use_queue=true`, emails sit in `jobs` table. Fallback: set `use_queue=false` to force `send()` for debugging.
- Debugging delivery:
    1. Check `config/mail.php` driver & .env vars.
    2. `php artisan tinker` -> trigger a manual `TwoFactorService::issueChallenge()`.
    3. Inspect logs for `2FA challenge issued` entries.
- Safe re-run: Regenerating a challenge deletes the previous pending record for same fingerprint.

## Testing Summary

Automated tests cover:

- Enabling/disabling with password validation.
- Challenge issued on new device & not on trusted one.
- Code verification success/failure, attempts decrement.
- Resend logic (basic path).
- Device trust persistence & revocation impact.

## Quick Dev Checklist

- [ ] Add rate limit to resend (e.g. Laravel RateLimiter).
- [ ] Harden cookies: `secure`, `sameSite=lax`, consider `Partitioned` when widespread.
- [ ] Add user-facing log / notification for enable/disable.
- [ ] Add background job to prune revoked / stale devices.

--
Last updated: {{ date('Y-m-d') }}
