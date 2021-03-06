<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendUpdate extends Mailable
{
    use Queueable, SerializesModels;


    public $users;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($users, $user)
    {
        $this->users = $users;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("User Profile Has Been Updated")->view('mails.sendUpdateBody');        
    }
}
