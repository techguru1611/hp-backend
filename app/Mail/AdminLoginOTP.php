<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AdminLoginOTP extends Mailable
{
    use Queueable, SerializesModels;

    private $data;

    public function setUserData($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.adminLoginOTP', [ 'data' => $this->data ])
                    ->subject('Login OTP');
    }
}
