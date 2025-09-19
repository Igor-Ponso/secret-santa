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
            ->subject(__('messages.emails.invitation.subject', ['group' => $this->group->name]))
            ->greeting(__('messages.emails.greeting'))
            ->line(__('messages.emails.invitation.intro', ['group' => $this->group->name]))
            ->line($this->group->description ?: '')
            ->action(__('messages.emails.invitation.accept_cta'), $acceptUrl)
            ->line(__('messages.emails.invitation.decline_line', ['url' => $declineUrl]))
            ->line(__('messages.emails.invitation.unexpected'));

        return $mail;
    }
}
