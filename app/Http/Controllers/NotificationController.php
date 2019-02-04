<?php

namespace App\Http\Controllers;

use App\Notification;
use App\User;
use Config;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;

class NotificationController extends Controller
{
    public function history(Request $request)
    {
        try {
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
            $userId = $request->user()->id;

            // Total Count of user notification
            $totalCount = Notification::userNotificationCount($userId);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, Config::get('constant.NOTIFICATION_HISTORY_PER_PAGE_LIMIT'), $totalCount);

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            // Transaction History
            $notificationHistory = Notification::userNotification($userId, Config::get('constant.NOTIFICATION_HISTORY_PER_PAGE_LIMIT'), $getPaginationData['offset'], $sort, $order);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'data' => $notificationHistory,
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
