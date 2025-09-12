<?php

namespace App\Notifications;

use App\Models\Group;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification
{
    // Synchronous like password reset / email verification

    public function __construct(
        protected Group $group,
        protected string $plainToken,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = route('invites.accept', $this->plainToken);
        $declineUrl = route('invites.decline', $this->plainToken);

        $mail = (new MailMessage)
            ->subject('Convite para participar do grupo: ' . $this->group->name)
            ->greeting('Olá!')
            ->line('Você foi convidado para participar do grupo "' . $this->group->name . '".')
            ->line($this->group->description ?: '')
            ->action('Aceitar Convite', $acceptUrl)
            ->line('Se não quiser participar, você pode recusar: ' . $declineUrl)
            ->line('Se você não esperava este e-mail, pode ignorá-lo.');

        return $mail;
    }
}
