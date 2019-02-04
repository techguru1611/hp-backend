<?php

namespace App\Http\Controllers\Admin;

use App\CountryCurrency;
use App\Http\Controllers\Controller;
use Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;

class CountryController extends Controller
{
    /**
     * To get country data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function list(Request $request) {
        try {
            // Rule validation -- Start
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
            // Rule validation -- End

            $searchByName = (isset($request->country_name_like) && !empty($request->country_name_like)) ? $request->country_name_like : null;
            $searchByCode = (isset($request->country_code_like) && !empty($request->country_code_like)) ? $request->country_code_like : null;
            $searchByCallingCall = (isset($request->calling_code_like) && !empty($request->calling_code_like)) ? $request->calling_code_like : null;
            $searchByOrder = (isset($request->sort_order_like) && !empty($request->sort_order_like)) ? $request->sort_order_like : null;

            // Total Count
            $totalCount = CountryCurrency::getCountryCodeCount($searchByName, $searchByCode, $searchByCallingCall, $searchByOrder);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'sort_order';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'ASC';

            // List data
            $countryData = CountryCurrency::getListing($request->limit, $getPaginationData['offset'], $sort, $order, $searchByName, $searchByCode, $searchByCallingCall, $searchByOrder);

            // All good so return the response
            $response = response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'data' => $countryData,
            ], 200);

            return $response;
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
     * To add country data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        try {
            // Rule validation - Start
            $rule = [
                'country_name' => 'required|max:50',
                'calling_code' => 'required|unique:country_currency,calling_code|max:4',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            // Rule validation - End

            // Save country code data
            $countryCode = new CountryCurrency([
                'country_name' => $request->country_name,
                'calling_code' => $request->calling_code,
            ]);
            $countryCode->save();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.COUNTRY_DATA_ADDED_SUCCESS_MESSAGE'),
                'data' => [
                    'country_id' => $countryCode->id
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
     * To update country data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update (Request $request) {
        try {
            // Rule validation - Start
            $rule = [
                'id' => 'required|regex:/^[1-9][0-9]{0,15}$/',
                'country_name' => 'required|max:50',
                'calling_code' => 'required|max:4|unique:country_currency,calling_code,' . $request->id,
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            // Rule validation - End

            // Get data of given ID
            $countryDetail = CountryCurrency::find($request->id);

            // Country data not found
            if ($countryDetail === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.empty_data_msg'),
                ], 404);
            }

            // Update record
            $countryDetail->update([
                'country_name' => $request->country_name,
                'calling_code' => $request->calling_code,
            ]);

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.COUNTRY_DATA_UPDATED_SUCCESS_MESSAGE'),
                'data' => [],
            ]);
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
     * To delete country data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            // Rule validation - Start
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
            // Rule validation - End

            $countryDetail = CountryCurrency::find($request->id);

            // Country data not found
            if ($countryDetail === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.empty_data_msg'),
                ], 404);
            }

            // Hard delete record
            $countryDetail->forcedelete();
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.COUNTRY_DATA_DELETED_SUCCESS_MESSAGE'),
                'data' => [],
            ]);
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
