<?php

namespace App\Http\Controllers\Admin;

use App\OTPManagement;
use Helpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Config;
use Validator;

class OTPManagementController extends Controller
{
    public function OTPManagementList(Request $request){
        try {
            $rule = [
                'page' => 'required|integer|min:1',
                'limit' => 'required|integer|min:1',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Search by user
            $searchByOTP = (isset($request->otp_like) && !empty($request->otp_like)) ? $request->otp_like : null;
            $searchBySentTO = (isset($request->otp_sent_to_like) && !empty($request->otp_sent_to_like)) ? $request->otp_sent_to_like : null;
            $searchByTransaction = (isset($request->transaction_type_like) && !empty($request->transaction_type_like)) ? $request->transaction_type_like : null;
            $searchByTime = (isset($request->created_at_like) && !empty($request->created_at_like)) ? $request->created_at_like : null;

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'otp_management.id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Total Count
            $totalCount = OTPManagement::getOTPListCount($searchByOTP, $searchBySentTO, $searchByTransaction, $searchByTime);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            $OTPList = OTPManagement::getOTPList($request->limit, $getPaginationData['offset'], $sort, $order, $searchByOTP, $searchBySentTO, $searchByTransaction, $searchByTime);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.otp_list_get_successfully'),
                'totalCount' => $totalCount,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'filter' => Config::get('constant.OTP_OPERATION_FILTER'),
                'data' => $OTPList
            ], 200);

        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.error_to_get_otp_list'),
            ], 500);
        }

    }
}
