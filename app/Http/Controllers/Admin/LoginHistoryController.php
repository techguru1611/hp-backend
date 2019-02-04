<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\UserLoginHistory;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;

class LoginHistoryController extends Controller
{
    /**
     * Get login history of users
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userLoginHistory(Request $request)
    {
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
            $searchByUser = (isset($request->user_id) && !empty($request->user_id)) ? $request->user_id : null;
            $searchByFullName = (isset($request->full_name_like) && !empty($request->full_name_like)) ? $request->full_name_like : null;
            $searchByBrowser = (isset($request->browser_like) && !empty($request->browser_like)) ? $request->browser_like : null;
            $searchByCreatedAt = (isset($request->created_at_like) && !empty($request->created_at_like)) ? $request->created_at_like : null;
            $searchByLocation = (isset($request->location_like) && !empty($request->location_like)) ? $request->location_like : null;

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Total Count
            $totalCount = UserLoginHistory::getLoginHistoryCount($searchByUser, $searchByFullName, $searchByBrowser, $searchByCreatedAt, $searchByLocation);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            $userLoginHistory = UserLoginHistory::getLoginHistory($request->limit, $getPaginationData['offset'], $sort, $order, $searchByUser, $searchByFullName, $searchByBrowser, $searchByCreatedAt, $searchByLocation);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.login_history_get_successfully'),
                'totalCount' => $totalCount,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'data' => $userLoginHistory
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.error_to_get_login_history'),
            ], 500);
        }

    }

}
