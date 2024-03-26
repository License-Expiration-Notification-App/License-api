<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;


class ConfirmNewRegistration extends Mailable //implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user; //make it public so that the view resource can access it
    public $password;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $password)
    {
        //
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $user = $this->user;
        $password = $this->password;
        return $this->view('emails.confirm_new_registration', compact('user', 'password'));
    }
}
