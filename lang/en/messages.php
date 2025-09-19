<?php

return [
    'invitations' => [
        'accepted' => 'Invitation accepted.',
        'declined' => 'Invitation declined.',
        'revoked' => 'Invitation revoked.',
        'resent' => 'Invitation resent.',
        'cannot_resend' => 'This invitation cannot be resent.',
        'already_owner' => 'You are already the owner of this group; no need to invite yourself.',
        'participant_exists' => 'This participant is already in the group.',
        'email_subject' => 'Invitation to join group: :group',
        'email_line_intro' => 'You have been invited to join the group ":group".',
        'email_accept_cta' => 'Accept Invitation',
        'email_decline_line' => 'If you do not wish to participate, you can decline here: :url',
        'email_unexpected' => 'If you did not expect this email, you may ignore it.',
    ],
    'draw' => [
        'already_done' => 'The draw has already been performed.',
        'failed' => 'Failed to generate draw.',
        'success' => 'Draw executed successfully!',
        'auto_subject' => 'Draw completed: :group',
        'auto_line' => 'The draw for group ":group" has been completed.',
        'auto_tip' => 'Tip: update your wishlist to help your Secret Santa choose a gift.',
        'email_greeting' => 'Hello!',
        'email_view_recipient_cta' => 'View my Secret Santa',
        'email_click_to_view' => 'Click below to see who you picked and start preparing the surprise!',
        'email_unexpected' => 'If you did not expect this email, you may ignore it.',
    ],
    'participants' => [
        'cannot_remove_owner' => 'You cannot remove the owner.',
        'cannot_remove_after_draw' => 'You cannot remove participants after the draw.',
        'insufficient_after_removal' => 'Cannot remove — group would not have enough participants.',
        'removed' => 'Participant removed.',
        'invalid_tab' => 'Invalid tab requested. Redirected to participants.',
        'already_owner' => 'You are already the group owner.',
        'user_already_owner' => 'That user is already the owner.',
        'user_not_participant' => 'User is not an accepted participant.',
        'already_participating' => 'You already participate in this group.',
        'cannot_approve' => 'Cannot approve.',
        'cannot_reject' => 'Cannot reject.',
    ],
    'wishlist' => [
        'item_added' => 'Wishlist item added',
        'item_updated' => 'Wishlist item updated',
        'item_removed' => 'Wishlist item removed',
        'initialized' => 'Wishlist initialized',
        'can_add_later' => 'You can add items later.',
    ],
    'onboarding' => [
        'accepted_you_can_add_later' => 'You can add items later.',
    ],
    'console' => [
        'run_due_draws' => [
            'option_dry' => 'Only shows what would be executed',
            'description' => 'Automatically runs draws for groups whose draw_at date is due and not yet processed.',
            'none_eligible' => 'No eligible groups.',
            'skip_insufficient' => '[skip] Group #:id (:name) - insufficient participants (:count)',
            'dry_run' => '[dry-run] Draw would be executed for group #:id (:name)',
            'race' => '[race] Group #:id already processed.',
            'fail' => '[fail] Draw failed for group #:id (:name)',
            'ok' => '[ok] Group #:id (:name) drawn. Notified: :notified',
            'table_headers' => ['Eligible', 'Executed', 'Insufficient participants', 'Fails', 'Notified']
        ]
    ],
    'emails' => [
        'greeting' => 'Hello!',
        'invitation' => [
            'subject' => 'Invitation to join group: :group',
            'intro' => 'You have been invited to join the group ":group".',
            'accept_cta' => 'Accept Invitation',
            'decline_line' => 'If you do not wish to participate, you can decline here: :url',
            'unexpected' => 'If you did not expect this email, you may ignore it.'
        ],
        'draw' => [
            'subject' => 'Draw completed: :group',
            'line' => 'The draw for group ":group" has been completed.',
            'tip' => 'Tip: update your wishlist to help your Secret Santa choose a gift.',
            'click_to_view' => 'Click below to see who you picked and start preparing the surprise!',
            'view_recipient_cta' => 'View my Secret Santa',
            'unexpected' => 'If you did not expect this email, you may ignore it.'
        ]
    ],
    'exclusions' => [
        'created' => 'Exclusion created.',
        'deleted' => 'Exclusion removed.',
        'duplicate' => 'This exclusion (or its inverse) already exists.',
        'locked_after_draw' => 'You cannot modify exclusions after the draw.',
        'invalid_participant' => 'One or more users are not valid accepted participants in this group.',
        'already_exists' => 'Exclusion already exists.',
        'not_found' => 'Exclusion not found.',
        'impossible' => 'These exclusions make a draw impossible.',
        'preview' => [
            'feasible' => 'A valid draw is possible with current exclusions.',
            'infeasible' => 'No valid draw is possible with current exclusions.'
        ]
    ],
];
