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
            ->subject(__('messages.emails.draw.subject', ['group' => $this->group->name]))
            ->greeting(__('messages.emails.greeting'))
            ->line(__('messages.emails.draw.line', ['group' => $this->group->name]))
            ->line(__('messages.emails.draw.click_to_view'))
            ->action(__('messages.emails.draw.view_recipient_cta'), $groupUrl)
            ->line(__('messages.emails.draw.tip'))
            ->line(__('messages.emails.draw.unexpected'));
    }
}
