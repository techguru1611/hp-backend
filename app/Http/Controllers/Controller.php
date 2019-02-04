<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Mail;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Send successful email to admin user for login OTP.
     *
     * @param array $data Array data of admin user to send login OTP
     *
     * @return void
     */
    public function sendAdminLoginOTP($data)
    {
        $this->adminLoginOTP->setUserData($data);
        Mail::to($data['email'])->send($this->adminLoginOTP);
    }

}
