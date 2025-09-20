<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $code)
    {
    }

    public function build()
    {
        return $this->subject('Your security code')
            ->view('emails.twofactor.code')
            ->with([
                'code' => $this->code,
                'user' => $this->user,
            ]);
    }
}
