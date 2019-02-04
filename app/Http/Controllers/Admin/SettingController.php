<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageUpload;
use App\Http\Controllers\Controller;
use App\Settings;
use Config;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use function PHPSTORM_META\elementType;
use Validator;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->settingLogoOriginalImageUploadPath = Config::get('constant.SETTING_LOGO_ORIGINAL_IMAGE_UPLOAD_PATH');
        $this->settingLogoThumbImageUploadPath = Config::get('constant.SETTING_LOGO_THUMB_IMAGE_UPLOAD_PATH');
        $this->settingLogoThumbImageHeight = Config::get('constant.SETTING_LOGO_THUMB_IMAGE_HEIGHT');
        $this->settingLogoThumbImageWidth = Config::get('constant.SETTING_LOGO_THUMB_IMAGE_WIDTH');
    }

    public function list (Request $request) {
        try {
            $settings = Settings::where('status', Config::get('constant.ACTIVE_FLAG'))->get()->each(function ($setting) {
                $setting->value = ($setting->slug == Config::get('constant.LOGO_SETTING_SLUG') ? ($setting->value != null && $setting->value != '' ? url('storage/' . $this->settingLogoOriginalImageUploadPath . $setting->value) : '') : $setting->value);
            });

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $settings,
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
     * Not in use as it is update single setting
     * To update setting
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSetting(Request $request)
    {
        try {
            $rule = [
                'name' => 'required',
                'slug' => 'required',
                'status' => ['required', Rule::in([Config::get('constant.ACTIVE_FLAG'), Config::get('constant.INACTIVE_FLAG'), Config::get('constant.DELETED_FLAG')])],
            ];

            $rule['value'] = 'required';
            // Update setting value
            if ($request->slug == Config::get('constant.LOGO_SETTING_SLUG')) {
                $rule['value'] = 'mimes:png,jpeg,jpg,bmp,gif|max:5120';
            } else if ($request->slug == Config::get('constant.E_VOUCHER_VALIDITY_SETTING_SLUG')) {
                $rule['value'] = 'required|integer'; // In minutes
            } else if ($request->slug == Config::get('constant.TRANSFER_FEE_SETTING_SLUG')) {
                $rule['value'] = 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/';
            } else if ($request->slug == Config::get('constant.ADD_TO_WALLET_FEE_SETTING_SLUG')) {
                $rule['value'] = 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/';
            }

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $settingData = Settings::where('slug', $request->slug)->where('status', '<>', Config::get('constant.DELETED_FLAG'))->first();
            // If slug not found in setting
            if ($settingData === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.SEETING_DATA_NOT_FOUND_OR_DELETED'),
                ], 200);
            }

            // Update setting value
            if ($request->slug == Config::get('constant.LOGO_SETTING_SLUG')) { // Image upload
                $response = $this->updateLogo($request);
            } else { // Update to given data
                $response = $this->updateSettingValue($request);
            }

            // All good so return the response
            return response()->json([
                'status' => $response['status'],
                'message' => $response['message'],
                'data' => (isset($response['data']) && !empty($response['data'])) ? $response['data'] : [],
            ], $response['statusCode']);
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
     * Not in use as it is update single setting
     * To update setting value of logo with image upload
     *
     * @param Object [$request] [Request Object]
     * @return array
     */
    public function updateLogo($request)
    {
        try {
            $setting = Settings::where('slug', $request->slug)->first();

            $data = $request->only('name', 'status');
            // upload logo
            if (!empty($request->file('value')) && $request->file('value')->isValid()) {
                /**
                 * @dev notes:
                 * originalPath & thumbPath these two path MUST be start with public folder otherwise file will not saved.
                 */
                $params = [
                    'originalPath' => 'public/' . ($this->settingLogoOriginalImageUploadPath),
                    'thumbPath' => 'public/' . ($this->settingLogoThumbImageUploadPath),
                    'thumbHeight' => $this->settingLogoThumbImageHeight,
                    'thumbWidth' => $this->settingLogoThumbImageWidth,
                    'previousImage' => $setting->value,
                ];

                $settingLogo = ImageUpload::storageUploadWithThumbImage($request->file('value'), $params);
                if ($settingLogo === false) {
                    return [
                        'status' => 0,
                        'message' => trans('apimessages.image_upload_error'),
                        'statusCode' => 200,
                    ];
                }
                // Update logo
                $data['value'] = $settingLogo['imageName'];
            }

            // Update setting data
            $setting->update($data);

            // All good so return the response
            return [
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'statusCode' => 200,
                'data' => $setting,
            ];
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return [
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'statusCode' => 500,
            ];
        }
    }

    /**
     * Not in use as it is update single setting
     * To update setting value
     *
     * @param Object [$request] [Request Object]
     * @return array
     */
    public function updateSettingValue($request)
    {
        try {
            $setting = Settings::where('slug', $request->slug)->first();

            // Update setting data
            $data = $request->only('name', 'value', 'status');
            $setting->update($data);
            
            // All good so return the response
            return [
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'statusCode' => 200,
                'data' => $setting,
            ];
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return [
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'statusCode' => 500,
            ];
        }
    }

    /**
     * To update settings
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        try {
            // Rule validation - Start
            $rule = [
                'settings.*.slug' => 'required',
                'settings.*.value' => 'required',
            ];

            $count = count($request->settings);

            foreach(range(0, ($count-1)) as $index) {
                if ($request->settings[$index]['slug'] == Config::get('constant.LOGO_SETTING_SLUG')) {
                    $rule['settings.' . $index . '.value'] = 'nullable|mimes:png,jpeg,jpg,bmp,gif|max:5120';
                } else if ($request->settings[$index]['slug'] == Config::get('constant.E_VOUCHER_VALIDITY_SETTING_SLUG')) {
                    $rule['settings.' . $index . '.value'] = 'required|integer'; // In Days
                } else if ($request->settings[$index]['slug'] == Config::get('constant.TRANSFER_FEE_SETTING_SLUG')) {
                    $rule['settings.' . $index . '.value'] = 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/';
                } else if ($request->settings[$index]['slug'] == Config::get('constant.ADD_TO_WALLET_FEE_SETTING_SLUG')) {
                    $rule['settings.' . $index . '.value'] = 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/';
                } else if ($request->settings[$index]['slug'] == Config::get('constant.COMPANY_EMAIL_SETTING_SLUG')){
                    $rule['settings.' . $index . '.value'] = 'required|email';
                } else if ($request->settings[$index]['slug'] == Config::get('constant.COMPANY_PHONE_NUMBER_SETTING_SLUG')){
                    $rule['settings.' . $index . '.value'] = 'required|min:10|max:17';
                } else if ($request->settings[$index]['slug'] == Config::get('constant.COMPANY_ADDRESS_SETTING_SLUG')){
                    $rule['settings.' . $index . '.value'] = 'required';
                } else if ($request->settings[$index]['slug'] == Config::get('constant.COMPANY_FACEBOOK_URL_SLUG')){
                    $rule['settings.' . $index . '.value'] = 'required|url';
                } else if ($request->settings[$index]['slug'] == Config::get('constant.COMPANY_TWITTER_URL_SLUG')){
                    $rule['settings.' . $index . '.value'] = 'required|url';
                } else if ($request->settings[$index]['slug'] == Config::get('constant.COMPANY_URL_SLUG')){
                    $rule['settings.' . $index . '.value'] = 'required|url';
                } else if ($request->settings[$index]['slug'] == Config::get('constant.DEFAULT_LATITUDE')){
                    $rule['settings.' . $index . '.value'] = ['required', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'];
                } else if ($request->settings[$index]['slug'] == Config::get('constant.DEFAULT_LONGITUDE')){
                    $rule['settings.' . $index . '.value'] = ['required', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'];
                }
            }

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                // Replace error message string
                foreach ($validator->messages()->all() as $key => $error_msg) {
                    if ((strpos($error_msg, ".slug")) !== FALSE) {
                        $messages[] = trans('apimessages.INCORRECT_OR_MISSING_SLUG_PARAMETER_FOUND');
                    }

                    if ((strpos($error_msg, ".value")) !== FALSE) {
                        $messages[] = trans('apimessages.INCORRECT_OR_MISSING_VALUE_PARAMETER_FOUND');
                    }
                }

                return response()->json([
                    'status' => 0,
                    'message' => $messages[0],
                ], 200);
            }
            // Rule validation - Ends

            DB::beginTransaction();
            // Update all Settings
            foreach ($request->settings as $setting) {
                $settingData = Settings::where('slug', $setting['slug'])->where('status', '<>', Config::get('constant.DELETED_FLAG'))->first();

                if ($settingData === null) {
                    DB::rollback();
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.INCORRECT_DATA_FOUND'),
                    ], 200);
                }

                if ($settingData->slug == Config::get('constant.LOGO_SETTING_SLUG')) {
                    // upload logo
                    if (!empty($setting['value']) && $setting['value']->isValid()) {
                        /**
                         * @dev notes:
                         * originalPath & thumbPath these two path MUST be start with public folder otherwise file will not saved.
                         */
                        $params = [
                            'originalPath' => 'public/' . ($this->settingLogoOriginalImageUploadPath),
                            'thumbPath' => 'public/' . ($this->settingLogoThumbImageUploadPath),
                            'thumbHeight' => $this->settingLogoThumbImageHeight,
                            'thumbWidth' => $this->settingLogoThumbImageWidth,
                            'previousImage' => $settingData->value,
                        ];

                        $settingLogo = ImageUpload::storageUploadWithThumbImage($setting['value'], $params);
                        if ($settingLogo === false) {
                            DB::rollback();
                            return response()->json([
                                'status' => 0,
                                'message' => trans('apimessages.image_upload_error'),
                            ], 200);
                        }
                        // Update setting data
                        $settingData->update([
                            'value' => $settingLogo['imageName'],
                        ]);
                    }
                } else {
                    // Update setting data
                    $settingData->update([
                        'value' => $setting['value'],
                    ]);
                }
            }
            DB::commit();

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.SETTINGS_DATA_UPDATED_SUCCESS_MESSAGE'),
                'data' => [],
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * To update settings image
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateImageSettings (Request $request) {
        try {
            $rule = [
                'slug' => ['required', Rule::in([Config::get('constant.LOGO_SETTING_SLUG')])],
                'value' => 'required|mimes:png,jpeg,jpg,bmp,gif|max:5120',
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }
            $settingData = Settings::where('slug', $request->slug)->where('status', '<>', Config::get('constant.DELETED_FLAG'))->first();
            // If slug not found in setting
            if ($settingData === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.SEETING_DATA_NOT_FOUND_OR_DELETED'),
                ], 200);
            }

            // Update Logo setting value
            if (!empty($request->file('value')) && $request->file('value')->isValid()) {
                /**
                 * @dev notes:
                 * originalPath & thumbPath these two path MUST be start with public folder otherwise file will not saved.
                 */
                $params = [
                    'originalPath' => 'public/' . ($this->settingLogoOriginalImageUploadPath),
                    'thumbPath' => 'public/' . ($this->settingLogoThumbImageUploadPath),
                    'thumbHeight' => $this->settingLogoThumbImageHeight,
                    'thumbWidth' => $this->settingLogoThumbImageWidth,
                    'previousImage' => $settingData->value,
                ];

                $settingLogo = ImageUpload::storageUploadWithThumbImage($request->file('value'), $params);
                if ($settingLogo === false) {
                    return [
                        'status' => 0,
                        'message' => trans('apimessages.image_upload_error'),
                        'statusCode' => 200,
                    ];
                }
                // Update setting data
                $settingData->update([
                    'value' => $settingLogo['imageName']
                ]);
            }

            // All good so return the response
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.SETTINGS_DATA_UPDATED_SUCCESS_MESSAGE'),
                'data' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
