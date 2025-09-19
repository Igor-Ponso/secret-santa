<?php

namespace App\Mail;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GroupInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Group $group,
        public string $plainToken,
    ) {
    }

    public function build(): self
    {
        return $this->subject(__('messages.emails.invitation.subject', ['group' => $this->group->name]))
            ->view('emails.group_invitation');
    }
}
