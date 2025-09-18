<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ParticipantDrawResultNotification extends Notification
{
    use Queueable; // Optional now; can remove if not using queues yet.

    public function __construct(
        protected Group $group,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $groupUrl = route('groups.show', $this->group);

        return (new MailMessage)
            ->subject('Sorteio concluído: ' . $this->group->name)
            ->greeting('Olá!')
            ->line('O sorteio do grupo "' . $this->group->name . '" foi concluído.')
            ->line('Clique abaixo para ver quem você tirou e começar a preparar a surpresa!')
            ->action('Ver meu amigo secreto', $groupUrl)
            ->line('Dica: atualize sua wishlist para ajudar seu amigo a escolher um presente.')
            ->line('Se você não esperava este e-mail, pode ignorá-lo.');
    }
}
