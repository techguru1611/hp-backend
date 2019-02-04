<?php

namespace App\Http\Controllers;

use App\CountryCurrency;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CountryController extends Controller
{
    /**
     * To get country data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Request $request)
    {
        try {
            $countryData = CountryCurrency::orderBy('sort_order', 'ASC')->get([
                'country_name',
                'country_code',
                'calling_code',
            ]);

            // All good so return the response with header
            $response = response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $countryData,
            ], 200);
            
            $response->header('Cache-Control', 'max-age=3600', true); // 1 Day (60 sec * 60 min)
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
}
