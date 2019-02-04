<?php

namespace App\Http\Controllers\Admin;

use App\ContactUs;
use App\Http\Controllers\Controller;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;

class ContactUsController extends Controller
{
    /**
     * Add Contact Us data.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function contactUs(Request $request)
    {
        try {
            $rule = [
                'name' => 'required|max:100',
                'phone' => 'required|max:20',
                'email' => 'nullable|email|max:100',
                'message' => 'required',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            // Save contact us data
            $contactUs = new ContactUs(array_filter($request->only('name', 'phone', 'email', 'message')));
            $contactUs->save();
            Log::info(strtr(trans('log_messages.contact_us'),[
                '<User>' => $request->phone
            ]));
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.contact_us_success_msg'),
                'data' => [
                    'contactUs' => $contactUs,
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

    /**
     * To get contact us data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Request $request)
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
            // Total Count
            $totalCount = ContactUs::count();

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            // Contact us data
            $requestData = ContactUs::orderBy('id', 'ASC')
                ->take($request->limit)
                ->offset($getPaginationData['offset'])
                ->get();

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
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
            ], 500);
        }
    }

}
