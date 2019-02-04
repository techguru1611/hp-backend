<?php

namespace App\Http\Controllers\Admin;

use App\AgentCommission;
use App\Commission;
use App\Http\Controllers\Controller;
use Config;
use DB;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Validator;
use App\Rules\CommissionStartRange;

class CommissionController extends Controller
{
    /**
     *
     * List commission management data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function list(Request $request) {
        try {
            $rule = [
                'page' => 'required|regex:/^[1-9][0-9]{0,15}$/',
                'limit' => 'required|regex:/^[1-9][0-9]{0,15}$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $searchByRange = (isset($request->amount_range_like) && !empty($request->amount_range_like)) ? $request->amount_range_like : null;
            $searchByHelapayShare = (isset($request->admin_commission_like) && !empty($request->admin_commission_like)) ? $request->admin_commission_like : null;

            // Total Count
            $totalCount = Commission::whereNull('agent_id');

            // Search by range
            if ($searchByRange !== null) {
                $totalCount = $totalCount->where('amount_range', 'LIKE', "%$searchByRange%");
            }

            // Search by helapay share
            if ($searchByHelapayShare !== null) {
                $totalCount = $totalCount->where('admin_commission', 'LIKE', "%$searchByHelapayShare%");
            }

            // $totalCount = $totalCount->count(
            //     DB::raw('CONCAT(`admin_commission`, "%")')
            // );
            $totalCount = $totalCount->count();

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'ASC';

            $requestData = Commission::getListing($request->limit, $getPaginationData['offset'], $sort, $order, $searchByRange, $searchByHelapayShare);

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'noOfPages' => $getPaginationData['noOfPages'],
                'data' => $requestData,
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     *
     * Update commission data to active / inactive
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request)
    {
        try {
            $rule = [
                'id' => 'required|regex:/^[1-9][0-9]{0,15}$/',
                'status' => ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG')])],
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $agentId = (isset($request->agent_id) && !empty($request->agent_id)) ? $request->agent_id : null;

            $commissionData = Commission::find($request->id);

            if ($agentId !== null) {
                $commissionData = Commission::where('id', $request->id)->where('agent_id', $agentId)->first();
            }

            // Data not found
            if ($commissionData === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.empty_data_msg'),
                ], 200);
            }

            // Update status
            $commissionData->update([
                'status' => $request->status,
            ]);

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.STATUS_UPDATED_SUCCESS_MESSAGE'),
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     *
     * Delete commission data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            $rule = [
                'id' => 'required|regex:/^[1-9][0-9]{0,15}$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            $commissionData = Commission::find($request->id);

            // Data not found
            if ($commissionData === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.empty_data_msg'),
                ], 200);
            }

            // Delete record
            $commissionData->delete();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.COMMISSION_DATA_DELETE_SUCCESS_MESSAGE'),
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     *
     * Add commission data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        try {
            $rule = [
                'start_range' => ['required', 'regex:/^[0-9]+(\.[0-9][0-9]?)?$/', new CommissionStartRange("end_range")],
                'end_range' => 'nullable|regex:/^[0-9]+(\.[0-9][0-9]?)?$/|greater_or_equal:start_range',
                'admin_commission' => 'required|regex:/[0-9]?[0-9]?[0-9]?(\.[0-9][0-9]?)?$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Save commission data
            $commission = new Commission([
                'start_range' => $request->start_range,
                'end_range' => $request->end_range,
                'amount_range' => number_format($request->start_range, 2) . ($request->end_range === null ? '+' : '-' . number_format($request->end_range, 2)),
                'admin_commission' => $request->admin_commission,
                'created_by' => $request->user()->id,
            ]);
            $commission->save();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.COMMISSION_DATA_ADDED_SUCCESS_MESSAGE'),
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     *
     * Update commission data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $rule = [
                'id' => 'required|regex:/^[1-9][0-9]{0,15}$/',
                //'start_range' => 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/',
                'start_range' => ['required', 'regex:/^[0-9]+(\.[0-9][0-9]?)?$/', new CommissionStartRange("end_range", $request->id)],
                'end_range' => 'nullable|regex:/^[0-9]+(\.[0-9][0-9]?)?$/|greater_or_equal:start_range',
                'admin_commission' => 'required|regex:/[0-9]?[0-9]?[0-9]?(\.[0-9][0-9]?)?$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $commission = Commission::where('id', $request->id)->first();

            // Data not found
            if ($commission === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.empty_data_msg'),
                ], 200);
            }

            // // Update commission data
            $commission->update([
                'start_range' => $request->start_range,
                'end_range' => $request->end_range,
                'amount_range' => number_format($request->start_range, 2) . ($request->end_range === null ? '+' : '-' . number_format($request->end_range, 2)),
                'admin_commission' => $request->admin_commission,
                'updated_by' => $request->user()->id,
            ]);
            Log::info(strtr(trans('log_messages.update_default_commission'),[
                '<Commission>' => $request->commission
            ]));

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.COMMISSION_DATA_UPDATED_SUCCESS_MESSAGE'),
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * Add or update agent default commission data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrUpdateDefaultAgentCommission(Request $request)
    {
        try {
            $rule = [
                'commission' => 'required|regex:/[0-9]?[0-9]?[0-9]?(\.[0-9][0-9]?)?$/',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $defaultAgentCommission = AgentCommission::whereNull('agent_id')->first();

            // If agent's default commission not added yet
            if ($defaultAgentCommission === null) {
                $defaultAgentCommission = new AgentCommission([
                    'commission' => $request->commission,
                ]);
                $defaultAgentCommission->save();
            } else {
                // Update default agent commission data
                $defaultAgentCommission->update([
                    'commission' => $request->commission,
                ]);
            }

            Log::info(strtr(trans('log_messages.update_default_commission'),[
                '<Commission>' => $request->commission
            ]));

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.AGENT_COMMISSION_DATA_UPDATED_SUCCESS_MESSAGE'),
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

    /**
     * To get default commission
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDefaultCommission(Request $request)
    {
        try {
            $response = AgentCommission::whereNull('agent_id')->first();

            $defaultCommission = ($response === null) ? 0.00 : $response->commission;
            Log::info(strtr(trans('log_messages.get_default_commission'),[
                '<Commission>' => $defaultCommission
            ]));
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => [
                    'defaultCommission' => $defaultCommission,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

}
