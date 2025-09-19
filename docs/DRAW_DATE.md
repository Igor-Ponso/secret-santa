# Draw Date Rules

`draw_at` is a date-only field (`YYYY-MM-DD`) controlling when automatic draws are eligible.

## Rationale

- Simpler mental model: a draw is a day event.
- Avoids timezone & DST confusion.
- Enables a single daily scheduler pass.

## Validation

`required | date_format:Y-m-d | after_or_equal:today`

## Persistence

- Cast as `date` on `Group` model.
- Service normalizes any input string to `Y-m-d`.

## Automatic Execution

- Scheduled command (00:05 server time) processes groups with `draw_at <= today` and not yet drawn.

## Manual Draw

Owner can run early if there are at least 2 accepted participants. UI shows a status banner with days remaining or overdue state.

## Post-Draw Immutability

After `has_draw = true`:

- Group updates blocked by policy (`GroupPolicy@update`).
- Exclusions become locked.
- Recipient wishlist visibility enabled.

## Migration Impact

Legacy timestamps are truncated to date semantics; time components are ignored. Future time-sensitive features would need a redesigned field.

## Testing

- `GroupDrawDateValidationTest`
- `GroupDrawDateUpdateValidationTest`
- Adjustments in `GroupTest` referencing `toDateString()`.
