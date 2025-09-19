<?php

return [
    'invitations' => [
        'accepted' => 'Convite aceito.',
        'declined' => 'Convite recusado.',
        'revoked' => 'Convite revogado',
        'resent' => 'Convite reenviado',
        'cannot_resend' => 'Não é possível reenviar este convite.',
        'already_owner' => 'Você já é o dono deste grupo; não é necessário convidar o próprio dono.',
        'participant_exists' => 'Este participante já está no grupo.',
        'email_subject' => 'Convite para participar do grupo: :group',
        'email_line_intro' => 'Você foi convidado para participar do grupo ":group".',
        'email_accept_cta' => 'Aceitar Convite',
        'email_decline_line' => 'Se não quiser participar, você pode recusar: :url',
        'email_unexpected' => 'Se você não esperava este e-mail, pode ignorá-lo.',
    ],
    'draw' => [
        'already_done' => 'O sorteio já foi realizado.',
        'failed' => 'Falha ao gerar sorteio',
        'success' => 'Sorteio realizado com sucesso!',
        'auto_subject' => 'Sorteio concluído: :group',
        'auto_line' => 'O sorteio do grupo ":group" foi concluído.',
        'auto_tip' => 'Dica: atualize sua wishlist para ajudar seu amigo a escolher um presente.',
        'email_greeting' => 'Olá!',
        'email_view_recipient_cta' => 'Ver meu amigo secreto',
        'email_click_to_view' => 'Clique abaixo para ver quem você tirou e começar a preparar a surpresa!',
        'email_unexpected' => 'Se você não esperava este e-mail, pode ignorá-lo.',
    ],
    'participants' => [
        'cannot_remove_owner' => 'Não é possível remover o dono.',
        'cannot_remove_after_draw' => 'Não é possível remover participantes após o sorteio.',
        'insufficient_after_removal' => 'Não é possível remover — grupo ficaria sem participantes suficientes.',
        'removed' => 'Participante removido.',
        'invalid_tab' => 'Aba inválida solicitada. Redirecionado para participantes.',
        'already_owner' => 'Você já é o dono do grupo.',
        'user_already_owner' => 'Esse usuário já é o dono.',
        'user_not_participant' => 'Usuário não é um participante aceito.',
        'already_participating' => 'Você já participa deste grupo.',
        'cannot_approve' => 'Não é possível aprovar.',
        'cannot_reject' => 'Não é possível recusar.',
    ],
    'wishlist' => [
        'item_added' => 'Item de wishlist adicionado',
        'item_updated' => 'Item de wishlist atualizado',
        'item_removed' => 'Item de wishlist removido',
        'initialized' => 'Wishlist inicializada',
        'can_add_later' => 'Você pode adicionar itens depois.',
    ],
    'onboarding' => [
        'accepted_you_can_add_later' => 'Você pode adicionar itens depois.',
    ],
    'console' => [
        'run_due_draws' => [
            'option_dry' => 'Apenas mostra o que seria executado',
            'description' => 'Executa automaticamente sorteios para grupos cuja data draw_at venceu e ainda não foram processados.',
            'none_eligible' => 'Nenhum grupo elegível.',
            'skip_insufficient' => '[skip] Grupo #:id (:name) - participantes insuficientes (:count)',
            'dry_run' => '[dry-run] Sorteio seria executado para grupo #:id (:name)',
            'race' => '[race] Grupo #:id já processado.',
            'fail' => '[fail] Sorteio falhou para grupo #:id (:name)',
            'ok' => '[ok] Grupo #:id (:name) sorteado. Notificados: :notified',
            'table_headers' => ['Elegíveis', 'Executados', 'Sem participantes', 'Falhas', 'Notificações']
        ]
    ],
    'emails' => [
        'greeting' => 'Olá!',
        'invitation' => [
            'subject' => 'Convite para participar do grupo: :group',
            'intro' => 'Você foi convidado para participar do grupo ":group".',
            'accept_cta' => 'Aceitar Convite',
            'decline_line' => 'Se não quiser participar, você pode recusar: :url',
            'unexpected' => 'Se você não esperava este e-mail, pode ignorá-lo.'
        ],
        'draw' => [
            'subject' => 'Sorteio concluído: :group',
            'line' => 'O sorteio do grupo ":group" foi concluído.',
            'tip' => 'Dica: atualize sua wishlist para ajudar seu amigo a escolher um presente.',
            'click_to_view' => 'Clique abaixo para ver quem você tirou e começar a preparar a surpresa!',
            'view_recipient_cta' => 'Ver meu amigo secreto',
            'unexpected' => 'Se você não esperava este e-mail, pode ignorá-lo.'
        ]
    ],
    'exclusions' => [
        'created' => 'Exclusão criada.',
        'deleted' => 'Exclusão removida.',
        'duplicate' => 'Esta exclusão (ou sua inversa) já existe.',
        'locked_after_draw' => 'Você não pode modificar exclusões após o sorteio.',
        'invalid_participant' => 'Um ou mais usuários não são participantes aceitos válidos neste grupo.',
        'already_exists' => 'Exclusão já existe.',
        'not_found' => 'Exclusão não encontrada.',
        'impossible' => 'Essas exclusões tornam o sorteio impossível.',
        'preview' => [
            'feasible' => 'Um sorteio válido é possível com as exclusões atuais.',
            'infeasible' => 'Nenhum sorteio válido é possível com as exclusões atuais.'
        ]
    ],
];
