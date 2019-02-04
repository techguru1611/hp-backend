<?php

namespace App\Helpers;

use App\AgentCommission;
use App\Commission;
use App\CountryCurrency;
use App\Settings;
use App\User;
use Config;

class CommissionService
{

    /**
     * To calculate commission on given amount
     * @param Numeric $amount
     * @param Integer $agentId
     */
    public static function calculateCommission($amount, $agentId = null)
    {
        try {
            // If transaction is done through agent
            if ($agentId != null) {
                // Get Agent Detail
                $agent = User::find($agentId);
                // Data not found for given agentId
                if ($agent === null) {
                    return [
                        'status' => 0,
                        'message' => trans('apimessages.AGENT_USER_DATA_NOT_FOUND'),
                        'code' => 200,
                    ];
                }
                // If given user is not agent
                if (!$agent->hasRole([Config::get('constant.AGENT_SLUG')])) {
                    return [
                        'status' => 0,
                        'message' => trans('apimessages.NOT_AUTHORIZE_TO_DO_THIS_ACTION'),
                        'code' => 200,
                    ];
                }
            }

            // Get helapay commission data
            $commissionData = Commission::where('start_range', '<=', $amount)->where('end_range', '>=', $amount)->first();

            $agentData = null;
            $defaultAgentCommissionData = null;

            if ($agentId != null) {
                // Get agent commission data
                $agentData = AgentCommission::where('agent_id', $agentId)->first();

                // Get default agent commission
                $defaultAgentCommissionData = AgentCommission::whereNull('agent_id')->first();
            }

            $netAmount = $amount;

            // Total commission
            $totalCommission = ($commissionData === null) ? Config::get('constant.DEFAULT_ADMIN_COMMISSION') : $commissionData->admin_commission;

            // Agent Commission
            $agentCommission = (($agentData != null) ? number_format($totalCommission * ($agentData->commission / 100), 2, '.', '') : (($defaultAgentCommissionData != null ? number_format($totalCommission * ($defaultAgentCommissionData->commission / 100), 2, '.', '') : 0)));

            // Helapay Commission
            $helapayCommission = number_format($totalCommission - $agentCommission, 2, '.', '');

            // Net amount
            $netAmount = number_format($amount - $totalCommission, 2, '.', '');

            // All good so return the response
            return [
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'code' => 200,
                'data' => [
                    'amount' => $amount,
                    'netAmount' => $netAmount,
                    'totalCommission' => $totalCommission,
                    'agentCommission' => $agentCommission,
                    'agentCommissionPerc' => ($agentData != null) ? $agentData->commission : 0,
                    'helapayCommission' => $helapayCommission,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'code' => 500,
            ];
        }
    }

    /**
     * To calculate helapay fee on given amount for given type
     * @param Numeric $amount
     * @param String $feeType [Calculation for transfer, e-voucher add to wallet type]
     * @return array
     */
    public static function calculateFee($amount, $feeType)
    {
        try {
            $netAmount = $amount;

            // Total & Helapay fee
            switch ($feeType) {
                case Config::get('constant.TRANSFER_MONEY_FEE'): // Transfer money fee calculation
                    $transferFee = Settings::where('slug', Config::get('constant.TRANSFER_FEE_SETTING_SLUG'))->where('status', Config::get('constant.ACTIVE_FLAG'))->first();
                    $totalFee = $helapayFee = ($transferFee != null ? (is_numeric($transferFee->value) ? floatval($transferFee->value) : 0) : 0); // Config::get('constant.DEFAULT_TRANSFER_MONEY_HELAPAY_FEE')
                    break;
                case Config::get('constant.E_VOUCHER_ADD_TO_WALLET_FEE'): // Add e-voucher to wallet fee calculation
                    $totalFee = $helapayFee = 0;
                    break;
                case Config::get('constant.SEND_E_VOUCHER_FEE'): // Send e-voucher fee calculation
                    $sendEvoucherFee = Settings::where('slug', Config::get('constant.SEND_E_VOUCHER_FEE_SETTING_SLUG'))->where('status', Config::get('constant.ACTIVE_FLAG'))->first();
                    $totalFee = $helapayFee = ($sendEvoucherFee != null ? (is_numeric($sendEvoucherFee->value) ? floatval($sendEvoucherFee->value) : 0) : 0); // Config::get('constant.DEFAULT_SEND_E_VOUCHER_FEE')
                    break;
                case Config::get('constant.BENEFICIARY_TRANSFER_FEE'): // Send e-voucher fee calculation
                    //$beneficiaryFee = Settings::where('slug', Config::get('constant.SEND_E_VOUCHER_FEE_SETTING_SLUG'))->where('status', Config::get('constant.ACTIVE_FLAG'))->first();
                    //$totalFee = $helapayFee = ($beneficiaryFee != null ? (is_numeric($beneficiaryFee->value) ? floatval($beneficiaryFee->value) : 0) : 0); // Config::get('constant.DEFAULT_SEND_E_VOUCHER_FEE')
                    $totalFee = $helapayFee = Config::get('constant.beneficiary_helapay_fees');
                    break;
                default: // code to be executed if feeType doesn't match any cases
                    $totalFee = $helapayFee = 0;
            }

            // All good so return the response
            return [
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'code' => 200,
                'data' => [
                    'amount' => $amount,
                    'totalCommission' => $totalFee,
                    'agentCommission' => 0,
                    'agentCommissionPerc' => 0,
                    'helapayCommission' => $totalFee,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'code' => 500,
            ];
        }
    }

    public static function convertAmount($amount, $sender_currency, $receiver_currency){
        $helapayFees = Config::get('constant.beneficiary_helapay_fees');
        $currency = CountryCurrency::where('country_code',$receiver_currency)->first();
        $net_amount = number_format($amount - $helapayFees, 2, '.', '');
        $receiver_amount = number_format($amount * $currency->unit,2, '.', '');

        $data = [
            'amount' => number_format($amount,2, '.', ''),
            'sender_currency' => $sender_currency,
            'helapay_fees' => number_format($helapayFees,2, '.', ''),
            'helapay_fees_in_percentage' => '',
            'net_amount' => $net_amount,
            'receiver_currency' => $receiver_currency,
            'receiver_amount' => $receiver_amount
        ];

        return $data;
    }
}
