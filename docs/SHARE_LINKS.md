# Share Links & Join Requests

This document describes the share link system that enables organic group growth beyond direct email invitations.

## Concepts

| Term         | Meaning                                                                                     |
| ------------ | ------------------------------------------------------------------------------------------- |
| Share Link   | A regenerable token (hashed at rest) that lets authenticated users request to join a group. |
| Join Request | A pending intent to join; must be approved by the group owner.                              |
| Attribution  | Linking a join request back to the share link for conversion metrics.                       |

## Flow Summary

1. Owner generates (or regenerates) share link.
2. Token URL is shared externally.
3. Visitor hits `/invites/{token}`:
    - If matches a standard invitation → invitation view logic.
    - Else if matches a share link → share link landing.
4. Authenticated non-participant may submit a join request (idempotent).
5. Owner approves or rejects pending requests.

## Storage

- `group_share_links(token_hash)` stores SHA-256 of the last active token.
- `group_join_requests` optionally references `share_link_id` for attribution.

## Security & Privacy

- Plain token never stored (hash only) – mitigates trivial database disclosure.
- Regeneration invalidates prior token (single active at a time).
- Duplicate join requests prevented via unique constraint (group_id + user_id).

## Session Handling

Guests have the token persisted in session (`pending_share_token`) and consumed on authentication to auto-create a join request.

## API Behaviors

| Status       | Condition                                    |
| ------------ | -------------------------------------------- |
| `share_link` | Valid share link token (no invitation match) |
| `invalid`    | No invitation and no share link match        |

## Testing Coverage

- Ensures no phantom invitation row is created by share link generation.
- Ensures post-registration flow creates attributed join request.

## Future Enhancements

- Token revocation (soft delete) UI & endpoint.
- Analytics: acceptance conversion rates.
- Multiple concurrent share links (campaign segmentation).
- Expiration (`expires_at`).
