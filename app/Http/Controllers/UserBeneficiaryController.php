<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;
use Config;
use DB;
use App\User;
use App\UserBeneficiary;
use App\UserBeneficiaryBankDetail;
use Carbon\Carbon;
use App\Helpers\Helpers;

class UserBeneficiaryController extends Controller
{
    protected $objUserBeneficiary = null;

    public function __construct()
    {
        $this->objUserBeneficiary = new UserBeneficiary();
        $this->objUserBeneficiaryBankDetail = new UserBeneficiaryBankDetail();
    }

    /**
     * Add or update Beneficiary information
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        $rule = [
            'name' => 'required',
            'nick_name' => 'required',
            'mobile_number' => 'required|min:10|max:17|regex:/^\+?\d+$/',
            /*'account_number' => 'required|confirmed|unique:user_beneficiaries,account_number,' . $request->id,
            'account_number_confirmation' => 'required',
            'swift_code' => 'required',*/
        ];
        $validator = Validator::make($request->all(),$rule);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->messages()->all()[0],
            ], 200);
        }

        try {
            $postedData = $request->all();
            $postedData['verification_status'] = Config::get('constant.VERIFIED_MOBILE_STATUS');
            $postedData['user_id'] = $request->user()->id;

            DB::beginTransaction();
            /*$otp = mt_rand(100000, 999999);

            $postedData['otp'] = $otp;
            $postedData['otp_created_date'] = Carbon::now();*/


            /*$savedBankAccountCount = $this->objUserBeneficiary->countBeneficiaryBank($request->user()->id);

            if ($savedBankAccountCount == 0) {
                $postedData['is_primary'] = 1;
            }*/

            $userBeneficiary = $this->objUserBeneficiary->insertUpdate($postedData);

            if ($userBeneficiary) {
                //$postedData["beneficiary_id"] = $userBeneficiary->id;

                //$userBeneficiaryBankDetail = $this->objUserBeneficiaryBankDetail->insertUpdate($postedData);

                /*$otpMessageText = trans('apimessages.BENEFICIARY_OTP_MESSAGE_TO_USER');
                $mobileNumber = ($request->user()->mobile_number);*/

                // $mobileNumber = '+91-9662321720';
                // Send OTP message
                /*$message = strtr($otpMessageText, [
                    '<OTP>' => $otp,
                ]);

                $sendOtp = Helpers::sendMessage($mobileNumber, $message);*/
                if (isset($request->id) && !empty($request->id) && $request->id > 0) {
                    $message = trans('apimessages.beneficiary_updated_suceessfully');
                }else{
                    $message = trans('apimessages.beneficiary_added_suceessfully');
                }

                DB::commit();
                return response()->json([
                    'status' => 1,
                    'message' => $message,
                    'data' => [
                        'beneficiary' => $userBeneficiary,
                    ],
                ], 200);
            }
            DB::rollback();

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * Verify the OTP before approving the add new Beneficiary
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOTP(Request $request)
    {
        $rules = [
            'beneficiary_id' => 'required',
            'bank_detail_id' => 'required',
            'otp' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->messages()->all()[0],
            ], 200);
        }

        $postedData = $request->only('beneficiary_id', 'bank_detail_id', 'otp');

        try {
            DB::beginTransaction();

            $verifiedData = $this->objUserBeneficiaryBankDetail->findByIdAndOTP($postedData);

            if ($verifiedData > 0) {
                $saveData = [];
                $saveData["id"] = $postedData["beneficiary_id"];
                $saveData['verification_status'] = Config::get('constant.APPROVED_BENEFICIARY_STATUS');

                $this->objUserBeneficiary->insertUpdate($saveData);

                $saveData = [];
                $saveData["id"] = $postedData["bank_detail_id"];
                $saveData["beneficiary_id"] = $postedData["beneficiary_id"];
                $saveData["otp"] = null;
                $saveData["otp_created_date"] = null;
                $saveData["otp_date "] = Carbon::now();
                $saveData['verification_status'] = Config::get('constant.APPROVED_BENEFICIARY_STATUS');

                $this->objUserBeneficiaryBankDetail->insertUpdate($saveData);
                DB::commit();

                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.beneficiary_added_suceessfully'),
                ], 200);
            } else {
                DB::rollback();

                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.beneficiary_otp_invalid'),
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * Get List of all Beneficiary for the logged in user.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll(Request $request)
    {
        $rule = [
            'page' => 'required|integer|min:1',
        ];

        $validator = Validator::make($request->all(), $rule);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->messages()->all()[0],
            ], 200);
        }

        try {
           // Total Count of user Beneficiary
            $totalCount = $this->objUserBeneficiary->getCountForAdmin();

           // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, Config::get('constant.BENEFICIARY_PER_PAGE_LIMIT'), $totalCount);

           // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

           // Get all Beneficiary list
            $userBeneficiaryList = $this->objUserBeneficiary->getAllWithPagingForAdmin(Config::get('constant.BENEFICIARY_PER_PAGE_LIMIT'), $getPaginationData['offset'], $sort, $order);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'data' => $userBeneficiaryList,
            ], 200);

        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    public function getUserBeneficiary(Request $request){
        try{
            $rule = [
                'page' => 'required|integer|min:1',
                'limit' => 'required|integer|min:1'
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $user_id = $request->user()->id;
            // Total Count of user Beneficiary
            $totalUserBeneficiary = $this->objUserBeneficiary->countTotalUserBeneficiary($user_id);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalUserBeneficiary);

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Get all Beneficiary list
            $userBeneficiaryList = $this->objUserBeneficiary->getAllUserBeneficiary($request->limit, $getPaginationData['offset'], $sort, $order, $user_id);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalUserBeneficiary,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'data' => $userBeneficiaryList,
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * Mark Bank account as primary for selected users.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsPrimary(Request $request)
    {
        $rules = [
            'beneficiary_id' => 'required|integer',
            'bank_detail_id' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->messages()->all()[0],
            ], 200);
        }

        $beneficiaryId = $request->beneficiary_Id;
        $bankDetailId = $request->bank_detail_id;

        try {
            DB::beginTransaction();

            $isDone = $this->objUserBeneficiaryBankDetail->markAsPrimary($beneficiaryId, $bankDetailId);

            if ($isDone) {
                DB::commit();
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.beneficiary_marked_primary_successfully'),
                ], 200);
            } else {
                DB::rollback();
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.beneficiary_not_found'),
                ], 200); 
            }
        } catch (\Exception $e) {
            DB::rollback();

            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * Add or update Beneficiary information
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $isDone = $this->objUserBeneficiary->removeBeneficiary($id);
            if ($isDone === true) {
                DB::commit();
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.beneficiary_deleted_successfully'),
                ], 200);
            }

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.beneficiary_not_found'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * Add or update Beneficiary information
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetails(Request $request, $id)
    {
        $user_id = $request->user()->id;
        try {
            $data = $this->objUserBeneficiary->findById($user_id, $id);

            if ($data) {
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.default_success_msg'),
                    'data' => $data
                ], 200);
            }

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.beneficiary_not_found'),
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * Add or update Beneficiary information
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBankDetails(Request $request)
    {
        // $user_id = $request->user()->id;
        $userId = $request->user_id;
        $bankDetailId = $request->bank_detail_id;
        $beneficiaryId = $request->beneficiary_id;

        try {
            $data = $this->objUserBeneficiary->getBankDetails($userId, $beneficiaryId, $bankDetailId);

            if ($data) {
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.default_success_msg'),
                    'data' => $data
                ], 200);
            }

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.beneficiary_not_found'),
            ], 200);

        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    public function getUserBeneficiaryList(Request $request){
        try{
            $id = $request->user()->id;
            $beneficiary = UserBeneficiary::where('user_id',$id)->get(['id','name','nick_name','mobile_number']);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $beneficiary
            ], 200);

        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'), [
                '<Message>' => $e->getMessage()
            ]));

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }
}