<?php

namespace App\Http\Controllers;

use App\Helpers\ImageUpload;
use App\Blog;
use App\Http\Controllers\Controller;
use Config;
use Illuminate\Support\Facades\Log;
use Validator;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct()
    {
        $this->blogOriginalImageUploadPath = Config::get('constant.BLOG_ORIGINAL_IMAGE_UPLOAD_PATH');
        $this->blogThumbImageUploadPath = Config::get('constant.BLOG_THUMB_IMAGE_UPLOAD_PATH');
        $this->blogThumbImageHeight = Config::get('constant.BLOG_THUMB_IMAGE_HEIGHT');
        $this->blogThumbImageWidth = Config::get('constant.BLOG_THUMB_IMAGE_WIDTH');
        $this->objBlog = new Blog();
    }

    /**
     * To get blogs data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Request $request)
    {
        try {
            $blog = Blog::all()->each(function ($blog) {
                $blog->image = ($blog->image != null && $blog->image != '') ? url($this->blogThumbImageUploadPath . $blog->image) : '';
            });
            Log::info(trans('log_message.get_blog_list'));
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => Blog::count(),
                'data' => $blog,
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
     * Add or update blogs data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrUpdateBlog(Request $request)
    {
        try {
            $rule = [
                'title' => 'required',
                'image' => 'required|mimes:png,jpeg,jpg,bmp|max:5120',
                'description' => 'required',
            ];

            if (isset($request->id) && $request->id > 0) {
                $rule['id'] = 'required|integer|min:1';
                $rule['image'] = 'mimes:png,jpeg,jpg,bmp|max:5120';
            }
            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()[0],
                ], 200);
            }

            $postData = $request->only('title', 'description');

            $previousImage = null;
            if (isset($request->id) && $request->id > 0) {
                $data = Blog::find($request->id);
                if ($data === null) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.empty_data_msg'),
                    ], 404);
                }
                $previousImage = $data->image;
                $postData['id'] = $request->id;
            }

            // Upload blog Image
            if (!empty($request->file('image')) && $request->file('image')->isValid()) {
                $params = [
                    'originalPath' => public_path($this->blogOriginalImageUploadPath),
                    'thumbPath' => public_path($this->blogThumbImageUploadPath),
                    'thumbHeight' => $this->blogThumbImageHeight,
                    'thumbWidth' => $this->blogThumbImageWidth,
                    'previousImage' => $previousImage,
                ];
                $blogImage = ImageUpload::uploadWithThumbImage($request->file('image'), $params);
                if ($blogImage === false) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.image_upload_error'),
                    ], 200);
                }
                $postData['image'] = $blogImage['imageName'];
            }

            $blog = $this->objBlog->insertUpdate($postData);
            if ($blog) {
                $blog->image = ($blog->image != null && $blog->image != '') ? url($this->blogThumbImageUploadPath . $blog->image) : '';
                $msg = (isset($request->id) && $request->id > 0) ? trans('apimessages.blog_updated_suceessfully') : trans('apimessages.blog_added_suceessfully');
                Log::info(strtr(trans('log_messages.blog_add_success'),[
                    '<User>' => $request->user()->email !== null ? $request->user()->email : $request->user()->mobile_number
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => $msg,
                    'data' => [
                        'blog' => $blog,
                    ],
                ], 200);
            } else {
                Log::info(strtr(trans('log_messages.blog_add_error'),[
                    '<User>' => $request->user()->email !== null ? $request->user()->email : $request->user()->mobile_number
                ]));
                $errorMsg = (isset($request->id) && $request->id > 0) ? trans('apimessages.error_updating_blog') : trans('apimessages.error_adding_blog');
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
     * Delete blog data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteBlog(Request $request, $id) {
        $blog = $this->objBlog->where('id', $id)->first();
        try {
            if ($blog === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.blog_not_found'),
                ], 404);
            } else {

                // Delete blog photo
                $params = [
                    'originalPath' => public_path($this->blogOriginalImageUploadPath),
                    'thumbPath' => public_path($this->blogThumbImageUploadPath),
                    'imageName' => $blog->photo
                ];
                $deletePhoto = ImageUpload::deleteImage($params);

                if ($deletePhoto === false) {
                    return response()->json([
                        'status' => 0,
                        'message' => trans('apimessages.default_image_delete_error_msg'),
                    ], 500);
                }
                $blog->delete();
                Log::info(strtr(trans('log_messages.delete_blog'),[
                    '<User>' => $request->user()->email !== null ? $request->user()->email : $request->user()->mobile_number
                ]));
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.blog_deleted_successfully'),
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