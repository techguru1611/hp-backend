<?php

namespace App\Http\Controllers;

use App\User;
use Config;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class MojaloopAPIController extends Controller
{
    /**
     * To create account in DFSP.
     *
     * @param [object] $user
     * @param [string] $password
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDFSPAccount($user, $password)
    {
        try {
            $client = new Client();
            $response = $client->request('POST', Config::get('constant.CREATE_DFSP_ACCOUNT'), [
                'auth' => [
                    Config::get('constant.MOJALOOP_DFSP2_USER_NAME'),
                    Config::get('constant.MOJALOOP_DFSP2_PASSWORD'),
                ],
                'json' => [
                    "identifier" => time() . $user->id,
                    "identifierTypeCode" => Config::get('constant.MOJALOOP_DFSP2_IDENTIFER_TYPE_CODE'),
                    "firstName" => $user->full_name,
                    "lastName" => "-",
                    "phoneNumber" => $user->mobile_number,
                    "accountName" => explode(' ', $user->full_name)[0] . $user->id,
                    "password" => $password,
                    "roleName" => Config::get('constant.MOJALOOP_ROLE_NAME'),
                    "balance" => "0",
                    "currencyCode" => Config::get('constant.MOJALOOP_DFSP2_DEFAULT_CURRENCY'),
                ],
            ]);

            return [
                'status' => 1,
                'data' => json_decode($response->getBody()),
                'code' => 200,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'data' => json_decode($e->getResponse()->getBody()->getContents()),
                'code' => $e->getCode(),
            ];
        }
    }
}
