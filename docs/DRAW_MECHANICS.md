# Draw Mechanics

This document explains how pair assignments are generated with exclusions and feasibility checks.

## Goals

- Ensure every participant gifts exactly one other participant.
- Avoid self-assignment.
- Respect explicit exclusion constraints between participants.
- Detect impossibility early and surface clear feedback.

## Algorithm Overview

A backtracking search with heuristic ordering:

1. Build constraint graph from exclusions (directed edges giver -> cannot gift -> receiver).
2. Order givers by ascending count of allowed receivers (most constrained first) to reduce branching.
3. For each giver, attempt assigning a receiver not already taken and not excluded.
4. Recurse; on dead-end, backtrack.
5. Abort after a configured upper bound on attempts to prevent pathological loops.

This approach balances simplicity with practical performance for typical group sizes (< 100 participants). For larger sets or heavy constraints, a future deterministic bipartite matching / SAT formulation could replace it.

## Feasibility Preview

Endpoint: `GET /groups/{group}/exclusions/preview`

Returns:

```json
{
    "feasible": true,
    "sample": { "giver_user_id": "receiver_user_id", "...": "..." },
    "message": "localized key"
}
```

- `sample` is one valid mapping (not persisted) when feasible.
- `feasible=false` implies either current or projected exclusions make the draw impossible.

## Failure Modes

| Case                    | Example                               | Response                                    |
| ----------------------- | ------------------------------------- | ------------------------------------------- |
| Self exclusion          | user excludes themselves              | Validation error                            |
| Duplicate exclusion     | same pair added twice                 | Validation error                            |
| Inverse duplicate       | A->B then B->A (if treated specially) | Validation error                            |
| Over-constrained graph  | One user excluded from all others     | Preview `feasible=false` + toast on create  |
| Concurrent modification | Multiple users editing exclusions     | Last write wins; preview encourages refresh |

## Manual vs Scheduled Draw

- Manual: Owner triggers early once minimum participants satisfied.
- Scheduled: Daily command processes due groups (`draw_at <= today`) that have not been drawn.

## Post-Draw Immutability

After assignments are committed:

- Exclusions locked.
- Group metadata becomes read-only (policy enforced).
- Recipient wishlist visibility enabled for each participant.

## Edge Cases Considered

- Two participants mutually excluding each other in a 2-person group → impossible.
- Large group with sparse exclusions → near-linear performance.
- Race where an exclusion is added after a feasibility preview but before draw → draw still validates constraints prior to persistence.

## Future Improvements

- Deterministic seeding for reproducible simulation in tests.
- Graph-based minimal repair suggestions when infeasible.
- UI visualization of exclusion graph density.
