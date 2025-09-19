<?php

return [
    'invitations' => [
        'accepted' => 'Invitation acceptée.',
        'declined' => 'Invitation refusée.',
        'revoked' => 'Invitation révoquée',
        'resent' => 'Invitation renvoyée',
        'cannot_resend' => "Impossible de renvoyer cette invitation.",
        'already_owner' => 'Vous êtes déjà le propriétaire de ce groupe ; inutile de vous inviter.',
        'participant_exists' => 'Ce participant est déjà dans le groupe.',
        'email_subject' => 'Invitation à rejoindre le groupe : :group',
        'email_line_intro' => 'Vous avez été invité à rejoindre le groupe ":group".',
        'email_accept_cta' => 'Accepter l\'invitation',
        'email_decline_line' => 'Si vous ne souhaitez pas participer, vous pouvez refuser ici : :url',
        'email_unexpected' => 'Si vous n\'attendiez pas cet e-mail, vous pouvez l\'ignorer.',
    ],
    'draw' => [
        'already_done' => 'Le tirage a déjà été effectué.',
        'failed' => 'Échec lors de la génération du tirage',
        'success' => 'Tirage exécuté avec succès !',
        'auto_subject' => 'Tirage terminé : :group',
        'auto_line' => 'Le tirage du groupe ":group" est terminé.',
        'auto_tip' => 'Astuce : mettez à jour votre wishlist pour aider votre Père Noël mystère à choisir un cadeau.',
        'email_greeting' => 'Bonjour !',
        'email_view_recipient_cta' => 'Voir mon Secret Santa',
        'email_click_to_view' => 'Cliquez ci-dessous pour découvrir qui vous avez tiré et préparer la surprise !',
        'email_unexpected' => 'Si vous n\'attendiez pas cet e-mail, vous pouvez l\'ignorer.',
    ],
    'participants' => [
        'cannot_remove_owner' => 'Impossible de retirer le propriétaire.',
        'cannot_remove_after_draw' => 'Impossible de retirer des participants après le tirage.',
        'insufficient_after_removal' => 'Impossible de retirer — le groupe n’aurait plus assez de participants.',
        'removed' => 'Participant retiré.',
        'invalid_tab' => 'Onglet invalide demandé. Redirection vers participants.',
        'already_owner' => 'Vous êtes déjà le propriétaire du groupe.',
        'user_already_owner' => 'Cet utilisateur est déjà le propriétaire.',
        'user_not_participant' => "L'utilisateur n'est pas un participant accepté.",
        'already_participating' => 'Vous participez déjà à ce groupe.',
        'cannot_approve' => 'Impossible d\'approuver.',
        'cannot_reject' => 'Impossible de refuser.',
    ],
    'wishlist' => [
        'item_added' => 'Élément de wishlist ajouté',
        'item_updated' => 'Élément de wishlist mis à jour',
        'item_removed' => 'Élément de wishlist supprimé',
        'initialized' => 'Wishlist initialisée',
        'can_add_later' => 'Vous pourrez ajouter des éléments plus tard.',
    ],
    'onboarding' => [
        'accepted_you_can_add_later' => 'Vous pourrez ajouter des éléments plus tard.',
    ],
    'console' => [
        'run_due_draws' => [
            'option_dry' => 'Affiche seulement ce qui serait exécuté',
            'description' => 'Exécute automatiquement les tirages pour les groupes dont la date draw_at est passée et non traités.',
            'none_eligible' => 'Aucun groupe éligible.',
            'skip_insufficient' => '[skip] Groupe #:id (:name) - participants insuffisants (:count)',
            'dry_run' => '[dry-run] Tirage serait exécuté pour le groupe #:id (:name)',
            'race' => '[race] Groupe #:id déjà traité.',
            'fail' => '[fail] Tirage échoué pour le groupe #:id (:name)',
            'ok' => '[ok] Groupe #:id (:name) tiré. Notifiés : :notified',
            'table_headers' => ['Éligibles', 'Exécutés', 'Participants insuffisants', 'Échecs', 'Notifiés']
        ]
    ],
    'emails' => [
        'greeting' => 'Bonjour !',
        'invitation' => [
            'subject' => 'Invitation à rejoindre le groupe : :group',
            'intro' => 'Vous avez été invité à rejoindre le groupe ":group".',
            'accept_cta' => 'Accepter l\'invitation',
            'decline_line' => 'Si vous ne souhaitez pas participer, vous pouvez refuser ici : :url',
            'unexpected' => 'Si vous n\'attendiez pas cet e-mail, vous pouvez l\'ignorer.'
        ],
        'draw' => [
            'subject' => 'Tirage terminé : :group',
            'line' => 'Le tirage du groupe ":group" est terminé.',
            'tip' => 'Astuce : mettez à jour votre wishlist pour aider votre Père Noël mystère à choisir un cadeau.',
            'click_to_view' => 'Cliquez ci-dessous pour découvrir qui vous avez tiré et préparer la surprise !',
            'view_recipient_cta' => 'Voir mon Secret Santa',
            'unexpected' => 'Si vous n\'attendiez pas cet e-mail, vous pouvez l\'ignorer.'
        ]
    ],
    'exclusions' => [
        'created' => 'Exclusion créée.',
        'deleted' => 'Exclusion supprimée.',
        'duplicate' => 'Cette exclusion (ou son inverse) existe déjà.',
        'locked_after_draw' => 'Vous ne pouvez pas modifier les exclusions après le tirage.',
        'invalid_participant' => 'Un ou plusieurs utilisateurs ne sont pas des participants acceptés valides dans ce groupe.',
        'already_exists' => 'L\'exclusion existe déjà.',
        'not_found' => 'Exclusion introuvable.',
        'impossible' => 'Ces exclusions rendent le tirage impossible.',
        'preview' => [
            'feasible' => 'Un tirage valide est possible avec les exclusions actuelles.',
            'infeasible' => 'Aucun tirage valide n\'est possible avec les exclusions actuelles.'
        ]
    ],
];
