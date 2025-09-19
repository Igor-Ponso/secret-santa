# Assignment Encryption & Rotation

This document details how Secret Santa assignment encryption works, why it exists, and how to operate key rotation safely.

## Goals

- Prevent trivial disclosure of giver→receiver pairs if the database is leaked.
- Allow forward-compatible crypto changes (versioning) without schema churn.
- Provide operational tooling to audit and rotate ciphers safely.

## Data Model

Column: `assignments.receiver_cipher`

Format: `v<version>:<payload>` where `<payload>` is Laravel's encrypted, base64-encoded JSON structure (IV, MAC, value).

Current version: configured at `config/encryption.php` (`assignments_version`) or `ASSIGNMENTS_ENCRYPTION_VERSION` env var.

Legacy column: `receiver_user_id` (now nullable) retained only for gradual migration; no longer populated. A later migration may remove it.

## Accessor Contract

Always read the receiver through the model accessor:

```php
$assignment->decrypted_receiver_id; // integer|null
```

Never parse or depend on the raw cipher format in application code.

## Commands

| Purpose                                      | Command                                  | Common Flags                                                |
| -------------------------------------------- | ---------------------------------------- | ----------------------------------------------------------- |
| Verify decryptability & version distribution | `php artisan assignments:verify-ciphers` | `--chunk=1000`                                              |
| Re-encrypt / rotate                          | `php artisan assignments:recrypt`        | `--from-version=v1`, `--force`, `--dry-run`, `--chunk=1000` |

### Verification

Outputs counts per version and failures. Exit code != 0 if any cipher is undecryptable.

Example:

```
php artisan assignments:verify-ciphers --chunk=2000
```

### Rotation Procedure Example (v1 -> v2)

1. Bump `assignments_version` to `v2`.
2. Deploy (new assignments use v2).
3. Dry-run impact: `php artisan assignments:recrypt --from-version=v1 --dry-run`
4. Execute rotation: `php artisan assignments:recrypt --from-version=v1`
5. Verify: `php artisan assignments:verify-ciphers`
6. (Optional) Force refresh: `php artisan assignments:recrypt --force`

### Failure Handling

If verification reports failures:

- Inspect logs for exception messages.
- DO NOT rotate until failures are resolved.
- Potential causes: APP_KEY mismatch between write and read environments, truncated cipher values.

### Threat Model

| Scenario          | Protection                                       | Residual Risk                                         |
| ----------------- | ------------------------------------------------ | ----------------------------------------------------- |
| DB leak only      | Pairings opaque (AES-GCM/CBC w/ MAC via Laravel) | Cipher volume implies participant counts              |
| DB + APP_KEY leak | Decryption possible                              | Standard secret management & infra hardening required |
| Tampered ciphers  | Verification detects (decryption failure)        | Need monitoring of verification job                   |

### Future Enhancements (Ideas)

- Envelope encryption per group (data key wrapped by master key / KMS).
- Automated scheduled verification + alerting.
- Periodic forced rotation (time-based).
- Optionally drop legacy nullable column.

## Checklist Before Dropping `receiver_user_id`

- All rows have null `receiver_user_id`.
- All ciphers have a version prefix.
- Historical migrations referencing the column updated or left benign.
- A backup taken prior to destructive migration.

## FAQ

**Does this hide the number of participants?**  
No. Row counts remain observable; only pair mapping is concealed.

**Can we change algorithms?**  
Yes—introduce `v2` handler that interprets a different format; keep the legacy branch for `v1` until migrated.

**What if rotation is interrupted?**  
The command processes in idempotent chunks; re-running will pick up remaining records.
