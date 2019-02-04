<?php

namespace App\Helpers;

use App\CountryCurrency;
use App\Roles;
use App\User;
use App\Notification;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Config;
use Mail;

class Helpers
{
    protected $client;

    public static function getRoleId($role_slug)
    {
        $role = new Roles();
        $roleId = $role->where('slug', $role_slug)->first();
        return ($roleId) ? $roleId->id : null;
    }

    public static function sendMessage($phoneNumber, $message)
    {
        try
        {
            /*Twilio SMS send Code Start*/

            // $sid    = Config::get('constant.TWILIO_ACCOUNT_SID');
            // $token  = Config::get('constant.TWILIO_AUTH_TOKEN');
            // $fromNumber  = Config::get('constant.TWILIO_PHONE_NUMBER');

            // $twilio = new TwilioOwnerClient($sid, $token);

            // $message = $twilio->messages
            //                   ->create($phoneNumber,
            //                            array(
            //                                'body' => $message,
            //                                'from' => $fromNumber
            //                            )
            //                   );
            // if ($message) {
            //     return true;
            // } else {
            //     return false;
            // }

            /*Twilio SMS send Code end*/

            /*INFOBIP SMS send Code start*/

            $userCredentials = Config::get('constant.INFOBIP_USER_ID') . ':' . Config::get('constant.INFOBIP_PASSWORD');

            $authEncoded = base64_encode($userCredentials);
            $data = array(
                "from" => "InfoSMS",
                "to" => $phoneNumber,
                "text" => $message,
            );

            $client = new Client();
            $response = $client->post('https://api.infobip.com/sms/1/text/single', array(
                'json' => $data,
                'headers' => array(
                    'Authorization' => 'Basic ' . $authEncoded,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ),
            )
            );

            if ($response) {
                return true;
            } else {
                return false;
            }
            /*INFOBIP SMS send Code end*/

            // Define recipients
            // $recipients = [$phoneNumber];
            // $url = Config::get('constant.SMS_GATEWAY_URL');
            // $api_token = Config::get('constant.SMS_GATEWAY_TOKEN');
            // $json = [
            //     'sender' => Config::get('constant.SMS_GATEWAY_SENDER'),
            //     'message' => $message,
            //     'recipients' => [],
            // ];
            // foreach ($recipients as $msisdn) {
            //     $json['recipients'][] = ['msisdn' => $msisdn];}

            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_URL, $url);
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            // curl_setopt($ch, CURLOPT_USERPWD, $api_token . ":");
            // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // $result = curl_exec($ch);
            // $json = json_decode($result);

            // if (isset($json->ids) && !empty($json->ids)) {
            //     return true;
            // }
            // return false;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * To get pagination data
     * @param integer $pageNo
     * @param integer $limit
     * @param integer $totalCount
     * @return array
     */
    public static function getAPIPaginationData($pageNo, $limit, $totalCount)
    {
        $noOfPages = (ceil($totalCount / $limit) == 0) ? 1 : ceil($totalCount / $limit);
        $atPage = (($pageNo > $noOfPages) ? $noOfPages : $pageNo);
        return [
            'offset' => (($atPage - 1) * $limit),
            'next' => ($noOfPages > $atPage) ? true : false,
            'previous' => ($atPage == 1) ? false : true,
            'noOfPages' => $noOfPages,
        ];
    }

    /**
     * Check that moibile number is verified or not
     * @param integer $mobileNumber
     * @param integer $ownNumber
     * @return array or boolean
     */
    public static function validateMobileNumber($mobileNumber, $ownNumber = null)
    {
        try {
            $userDetail = User::where('mobile_number', $mobileNumber)->first();
            if ($userDetail) {
                if ($mobileNumber == $ownNumber) {
                    return [
                        'status' => 0,
                        'message' => trans('apimessages.self_transaction_not_allowed'),
                        'code' => Config::get('constant.SELF_MOBILE_ERROR_CODE'),
                    ];
                }

                if ($userDetail->verification_status == Config::get('constant.VERIFIED_MOBILE_STATUS')) {
                    return true;
                } else if ($userDetail->verification_status == Config::get('constant.UNREGISTERED_USER_STATUS')) {
                    return [
                        'status' => 0,
                        'message' => trans('apimessages.empty_data_msg'),
                        'code' => Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE'),
                    ];
                } else {
                    return [
                        'status' => 0,
                        'message' => trans('apimessages.mobile_is_not_verified'),
                        'code' => Config::get('constant.UNVERIFIED_MOBILE_ERROR_CODE'),
                    ];
                }
            } else {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.ACCOUNT_NOT_EXIST_WITH_THIS_MOBILE_NUMBER'),
                    'code' => Config::get('constant.UNREGISTERED_MOBILE_ERROR_CODE'),
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'code' => Config::get('constant.UNKNOWN_ERROR_CODE'),
            ];
        }
    }

    /**
     * Check that moibile number role wise, is verified or not
     * @param integer $mobileNumber
     * @return array or boolean
     */
    public static function validateMobileNumberRoleWise($mobileNumber, $roleId)
    {
        try {
            $userDetail = User::where(['mobile_number' => $mobileNumber, 'role_id' => $roleId])->first();
            if ($userDetail) {
                if ($userDetail->verification_status == Config::get('constant.VERIFIED_MOBILE_STATUS')) {
                    return true;
                } else {
                    return [
                        'status' => 0,
                        'message' => trans('apimessages.mobile_is_not_verified'),
                    ];
                }
            } else {
                return [
                    'status' => 0,
                    'message' => trans('apimessages.empty_data_msg_for_agent_cash_in_out'),
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ];
        }
    }

    /**
     * Send mail
     * @param string $to
     * @param string $subject
     * @param string $templatename
     * @param array $templatedata
     * @return boolean
     */
    public static function sendMail($to, $subject, $templatename, $templatedata)
    {

        $data = array();
        $data['subject'] = $subject;
        $data['toEmail'] = $to;

        Mail::send(['html' => 'emails.' . $templatename], $templatedata, function ($message) use ($data) {
            $message->subject($data['subject']);
            $message->to($data['toEmail'], '');
        });
    }

    /**
     * Mask string to $maskingCharacter before 4 digits
     * @param string $number
     * @param string $maskingCharacter
     * @return string Masked string
     */
    public static function maskString($number, $maskingCharacter = 'X')
    {
        return str_repeat($maskingCharacter, strlen($number) - 4) . substr($number, -4);
    }

    /**
     * Validate given string that is it valid email or not
     * @param string $email
     * @return boolean
     */
    public static function validateEmail($email)
    {
        if (is_array($email) || is_numeric($email) || is_bool($email) || is_float($email) || is_file($email) || is_dir($email) || is_int($email)) {
            return false;
        } else {
            $email = trim(strtolower($email));
            if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                return true;
            } else {
                $pattern = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';
                return (preg_match($pattern, $email) === 1) ? true : false;
            }
        }
    }

    /**
     * Check last activity of user and update or return false if max time exist
     * @param object $user
     * @return boolean
     */
    public static function checkLastActivity($user)
    {
        try {
            if (is_object($user)) {
                if ($user->last_activity_at === null || (strtotime($user->last_activity_at) + Config::get('constant.MAX_ALLOW_TIME_TO_IDLE_IN_SECONDS')) > strtotime(date('Y-m-d H:i:s'))) {
                    $user->update([
                        'last_activity_at' => Carbon::now(),
                    ]);
                    return true;
                }
                return false;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Split mobile number and country code
     * @param object $user
     * @return boolean
     */
    public static function splitMobileNumber($mobileNumber)
    {
        try {
            // Separate country code from mobile number
            $countryCodeList = CountryCurrency::orderBy('sort_order', 'ASC')->pluck('calling_code');

            foreach ($countryCodeList as $_countryCodeList) {
                if (strpos($mobileNumber, $_countryCodeList) !== false) {
                    return [
                        'country_code' => $_countryCodeList,
                        'mo_no' => str_replace($_countryCodeList, '', $mobileNumber),
                    ];
                }
            }
            return [
                'country_code' => '',
                'mo_no' => $mobileNumber,
            ];
        } catch (\Exception $e) {
            return [
                'country_code' => '',
                'mo_no' => $mobileNumber,
            ];
        }
    }

    public static function saveNotificationMessage ($user, $message) {
        return $user->notification()->save(new Notification([
            'message' => $message
        ]));
    }

    /**
     * Function will be conver the Array's NULL values to EMPTY STRING. This will help iOS and Android developer parse the JSON
     */
    public static function convertNullToEmptyString($arrayTobeUpdate) {
        return array_map( function($filterMe) {
            if(is_array($filterMe)) {
                // return array_map( function($innnerArrayfilterMe) {
                //     return is_null($innnerArrayfilterMe) ? "" : $innnerArrayfilterMe;
                // }, $filterMe);
                return Helpers::convertNullToEmptyString($filterMe);
            } else {
                return is_null($filterMe) ? "" : $filterMe;
            }                
        }, $arrayTobeUpdate);
    } 

    /**
     * Retrive the KYC document Status Id from the name.
     */
    public static function getKYCDocumentStatusIdFromName($searchByKYCStatus) {
        /* 0 - Pending | 1 = Approved | 2 = Rejected | 3 = Correction */
        $searchStatus = 0;        
        switch(strtolower($searchByKYCStatus)) {
            case strtolower("Pending"):
                $searchStatus = 0;
                break;
            case strtolower("Approved"):
                $searchStatus = 1;
                break;
            case strtolower("Rejected"):
                $searchStatus = 2;
                break;
            case strtolower("Correction"):
                $searchStatus = 3;
                break;
        }
        return $searchStatus;
    }

    public static function getTransactionType($type){
        $transactionType = '';
        switch ($type){
            case Config::get('constant.ADD_MONEY_TRANSACTION_TYPE'):
                $transactionType = 'Add Money';
                break;
            case Config::get('constant.WITHDRAW_MONEY_TRANSACTION_TYPE'):
                $transactionType = 'Withdraw Money';
                break;
            case Config::get('constant.ONE_TO_ONE_TRANSACTION_TYPE'):
                $transactionType = 'One to One';
                break;
            case Config::get('constant.CASH_IN_TRANSACTION_TYPE'):
                $transactionType = 'Cash In';
                break;
            case Config::get('constant.E_VOUCHER_TRANSACTION_TYPE'):
                $transactionType = 'E Voucher';
                break;
            case Config::get('constant.REDEEMED_TRANSACTION_TYPE'):
                $transactionType = 'Redeemed';
                break;
            case Config::get('constant.E_VOUCHER_CASHOUT_TRANSACTION_TYPE'):
                $transactionType = 'E Voucher Cash Out';
                break;
            case Config::get('constant.CASH_OUT_TRANSACTION_TYPE'):
                $transactionType = 'Cash Out';
                break;
            case Config::get('constant.ADD_COMMISSION_TO_WALLET_TRANSACTION_TYPE'):
                $transactionType = 'Add Commission to Wallet';
                break;
            case Config::get('constant.WITHDRAW_MONEY_FROM_COMMISSION_TRANSACTION_TYPE'):
                $transactionType = 'Withdraw Money from Commission';
                break;
        }
    }

    /**
     * Convert a csv to an array
     *
     * @param string $filename
     * @param string $delimiter
     * @return array|bool
     */
    public static function convertCSVToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $header = null;
        $data = [];
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$header) {
                    $header = $row;
                }
                else {
                    if (count($header) == count($row)) {
                        $data[] = array_combine($header, $row);
                    }
                }
            }
            fclose($handle);
        }

        return $data;
    }
}