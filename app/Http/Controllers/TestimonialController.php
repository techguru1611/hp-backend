<?php

namespace App\Http\Controllers;

use App\Helpers\ImageUpload;
use App\Http\Controllers\Controller;
use App\Testimonial;
use Config;
use Illuminate\Support\Facades\Log;
use Validator;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function __construct()
    {
        $this->testimonialUserOriginalImageUploadPath = Config::get('constant.TESTIMONIAL_USER_ORIGINAL_IMAGE_UPLOAD_PATH');
        $this->testimonialUserThumbImageUploadPath = Config::get('constant.TESTIMONIAL_USER_THUMB_IMAGE_UPLOAD_PATH');
        $this->testimonialUserThumbImageHeight = Config::get('constant.TESTIMONIAL_USER_THUMB_IMAGE_HEIGHT');
        $this->testimonialUserThumbImageWidth = Config::get('constant.TESTIMONIAL_USER_THUMB_IMAGE_WIDTH');
        $this->objTestimonial = new Testimonial();
    }

    /**
     * To get testiomonials data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Request $request)
    {
        try {
            $testimonial = Testimonial::all()->each(function ($testimonial) {
                $testimonial->photo = ($testimonial->photo != null && $testimonial->photo != '') ? url($this->testimonialUserThumbImageUploadPath . $testimonial->photo) : '';
            });
            Log::info(trans('log_messages.get_testimonial'));
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => Testimonial::count(),
                'data' => $testimonial,
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
     * Add or update testiomonials data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrUpdateTestimonials(Request $request)
    {
        try {
            $rule = [
                'name' => 'required',
                'photo' => 'required|mimes:png,jpeg,jpg,bmp|max:5120',
                'position' => 'required',
                'description' => 'required',
            ];

            if (isset($request->id) && $request->id > 0) {
                $rule['id'] = 'required|integer|min:1';
                $rule['photo'] = 'mimes:png,jpeg,jpg,bmp|max:5120';
            }
            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $postData = $request->only('name', 'position', 'description');

            $previousImage = null;
            if (isset($request->id) && $request->id > 0) {
                $data = Testimonial::find($request->id);
                if ($data === null) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.empty_data_msg'),
                    ], 404);
                }
                $previousImage = $data->photo;
                $postData['id'] = $request->id;
            }

            // Upload testimonial Image
            if (!empty($request->file('photo')) && $request->file('photo')->isValid()) {
                $params = [
                    'originalPath' => public_path($this->testimonialUserOriginalImageUploadPath),
                    'thumbPath' => public_path($this->testimonialUserThumbImageUploadPath),
                    'thumbHeight' => $this->testimonialUserThumbImageHeight,
                    'thumbWidth' => $this->testimonialUserThumbImageWidth,
                    'previousImage' => $previousImage,
                ];
                $testimonialPhoto = ImageUpload::uploadWithThumbImage($request->file('photo'), $params);
                if ($testimonialPhoto === false) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.image_upload_error'),
                    ], 200);
                }
                $postData['photo'] = $testimonialPhoto['imageName'];
            }

            $testimonial = $this->objTestimonial->insertUpdate($postData);
            Log::info(strtr(trans('log_messages.add_testimonial'),[
                '<User>' => $request->user()->email !== null ? $request->user()->email : $request->user()->mobile_number
            ]));
            if ($testimonial) {
                $testimonial->photo = ($testimonial->photo != null && $testimonial->photo != '') ? url($this->testimonialUserThumbImageUploadPath . $testimonial->photo) : '';
                $msg = (isset($request->id) && $request->id > 0) ? trans('apimessages.testimonial_updated_suceessfully') : trans('apimessages.testimonial_added_suceessfully');
                return response()->json([
                    'status' => 1,
                    'message' => $msg,
                    'data' => [
                        'testimonial' => $testimonial,
                    ],
                ], 200);
            } else {
                Log::error(strtr(trans('log_messages.add_testimonial_error'),[
                    '<User>' => $request->user()->email !== null ? $request->user()->email : $request->user()->mobile_number
                ]));
                $errorMsg = (isset($request->id) && $request->id > 0) ? trans('apimessages.error_updating_testimonial') : trans('apimessages.error_adding_testimonial');
                return response()->json([
                    'status' => 0,
                    'message' => $errorMsg,
                ], 200);
            }
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
     * Delete testiomonials data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteTestimonials(Request $request, $id) {
        $testimonial = $this->objTestimonial->where('id', $id)->first();
        try {
            if ($testimonial === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.testimonial_not_found'),
                ], 404);
            } else {

                // Delete Testimonial photo
                $params = [
                    'originalPath' => public_path($this->testimonialUserOriginalImageUploadPath),
                    'thumbPath' => public_path($this->testimonialUserThumbImageUploadPath),
                    'imageName' => $testimonial->photo
                ];
                $deletePhoto = ImageUpload::deleteImage($params);

                if ($deletePhoto === false) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.default_image_delete_error_msg'),
                    ], 500);
                }
                $testimonial->delete();
                Log::error(strtr(trans('log_messages.testimonial_delete'),[
                    '<User>' => $request->user()->email !== null ? $request->user()->email : $request->user()->mobile_number
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.testimonial_deleted_successfully'),
                ]);
            }
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
