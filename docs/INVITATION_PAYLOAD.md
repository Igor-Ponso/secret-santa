# Invitation Payload Contract

Normalized JSON returned by the invitation landing endpoint `/invites/{token}`.

## Structure

```jsonc
{
  "invitation": {
    "group": { "id": 1, "name": "", "description": "" } | null,
    "inviter": { "id": 2, "name": "" } | null,
    "email": "user@example.com" | null, // Only if viewer email matches invitation email
    "status": "pending|accepted|declined|revoked|expired|invalid|share_link",
    "expired": true,
    "revoked": false,
    "token": "<masked or null>",
    "viewer": {
      "authenticated": true,
      "participates": false,
      "is_owner": false,
      "email_mismatch": false,
      "can_accept": true,
      "can_request_join": false,
      "join_requested": false
    }
  }
}
```

## Behavior Notes

1. If `participates` is true the server redirects to the group show page instead of returning the payload.
2. `can_accept` and `can_request_join` are mutually exclusive.
3. `join_requested` prevents duplicate submissions client-side.
4. Email is omitted unless the authenticated viewer owns the invitation email.
5. `status = share_link` is used to differentiate share link landings from invalid tokens.

## Edge Cases

| Case                | Handling                                    |
| ------------------- | ------------------------------------------- |
| Expired invitation  | status = `expired`, actions disabled        |
| Revoked invitation  | status = `revoked`, actions disabled        |
| Declined invitation | status = `declined`, can still view summary |
| Invalid token       | status = `invalid`, minimal payload         |
| Share link token    | status = `share_link`, join request path    |

## Extension Points

- Additional viewer flags (e.g., `can_resend`) can be added without breaking consumers if treated as optional.
- Analytics counters could be appended under `invitation.meta` namespace.
