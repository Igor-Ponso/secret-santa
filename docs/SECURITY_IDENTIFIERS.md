# Identifier Strategy & Public Exposure

This document describes how identifiers are exposed (or intentionally _not_ exposed) publicly, and the rationale behind each decision.

## Goals

1. Minimize resource enumeration and inference of system scale.
2. Avoid leaking existence of private resources through differential status codes.
3. Preserve internal simplicity and performance (integer primary keys) while offering non-enumerable public references.
4. Support future evolution (slugs, rotations) without breaking existing links.

## Current State (Delivered)

| Surface                            | Identifier                              | Public?                                | Notes                                                                                                      |
| ---------------------------------- | --------------------------------------- | -------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Authenticated group routes         | Integer `id`                            | No (auth required)                     | Protected by membership middleware returning 404 when not a participant (prevents existence confirmation). |
| Public group landing (`/g/{code}`) | `public_code` (Base62 random, 12 chars) | Yes                                    | Non-sequential, unique, can be rotated in future if needed.                                                |
| Invitations (public)               | Hashed token lookup                     | Yes (plain token only in emailed link) | Token not reversible server-side; enumeration impractical.                                                 |
| Assignments encryption             | Versioned cipher                        | No                                     | Receiver identities protected at rest.                                                                     |

## 404 over 403 Policy

For membership-protected group pages, unauthorized access triggers a _404 Not Found_ instead of _403 Forbidden_. This removes a low-signal side-channel (confirming the existence of a group the user cannot access).

Implementation: middleware `EnsureGroupMembership` throws `ModelNotFoundException` on non-membership, letting Laravel's exception handler deliver a 404. A custom Inertia 404 page (`Errors/NotFound.vue`) enhances UX without revealing internal details.

## `public_code`

Added to `groups` table:

- 12-character Base62 random string stored in `public_code` (column length 16 to allow future versioning/prefixing).
- Generated on create and backfilled for existing rows via migration.
- Exposed only on the public landing page route; not included automatically in authenticated bulk responses unless explicitly needed.
- Accessor `public_url` (lazy) returns full URL if route is registered.

### Why not UUID/ULID now?

- Internal integer keys remain fast (index + FK constraints) and are not exposed publicly.
- Introducing UUID globally adds storage & index overhead without a concrete attacker benefit at present.
- New _public_ surfaces use non-enumerable codes already, fulfilling the security goal.

### Why not Hashids?

- Hashids are reversible obfuscation; secrecy depends entirely on salt.
- Random codes (true entropy) are simpler and do not require salt rotation planning.

## Future Evolution (Optional)

| Potential Step              | Trigger                                | Approach                                                                                  |
| --------------------------- | -------------------------------------- | ----------------------------------------------------------------------------------------- |
| Add slugs for prettier URLs | Marketing / share UX                   | `/g/{slug}-{code}` resolving internally by `{code}`, canonical redirect if slug mismatch. |
| `public_code` rotation      | Compromise / invalidation need         | Add `rotated_public_codes` table or soft-expire old codes with 301 redirect window.       |
| ULIDs for new domains       | New tables (activities, notifications) | Start with native ULID PKs using Laravel `HasUlids` trait.                                |
| Full migration off integers | Compliance / multi-tenant isolation    | Dual-column strategy (`ulid` + legacy `id`), phased read/write, final switch & cleanup.   |

## Threat Model Summary

| Threat                                           | Mitigation                                                                                  |
| ------------------------------------------------ | ------------------------------------------------------------------------------------------- |
| Group existence enumeration via incrementing IDs | Membership middleware returns 404 for unauthorized; no public ID-based endpoints.           |
| Predicting next group ID                         | Irrelevant to public surface; `public_code` is random.                                      |
| Brute forcing public codes                       | 62^12 space (~2^71). Rate limiting + standard request logging can detect anomalous probing. |
| Invitation token harvesting                      | Tokens only distributed out-of-band (email); stored hashed; no enumeration endpoint.        |
| Cipher downgrade / receiver identity leakage     | Versioned encryption + operational verification commands.                                   |

## Operational Notes

- Logs: high 404 rate on `/g/*` paths can indicate scanning — consider alert thresholds.
- Rotation: provide future console command to regenerate `public_code` for a group if compromise suspected.
- Testing: Feature tests assert 404 for non-member `/groups/{id}` and 200/404 for valid/invalid `/g/{code}`.

## Maintenance Checklist

| Action                              | Cadence           | Owner              |
| ----------------------------------- | ----------------- | ------------------ |
| Review 404 uniformity strategy      | Quarterly         | Security / Backend |
| Assess need for slugs or rotations  | Product-driven    | PM / Engineering   |
| Run assignments cipher verification | Scheduled (daily) | Ops                |

---

Questions or adjustments: open an issue with label `security-identifiers`.
