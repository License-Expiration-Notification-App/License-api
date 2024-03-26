<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;


class PassKey extends Mailable // implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user; //make it public so that the view resource can access it
    public $token;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $token)
    {
        //
        $this->user = $user;
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $user = $this->user;
        $token = $this->token;
        return $this->view('emails.send_pass_key', compact('user', 'token'));
    }
}
