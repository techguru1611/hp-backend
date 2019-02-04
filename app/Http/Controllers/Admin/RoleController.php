<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Log;
use Validator;
use Helpers;
use App\Roles;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->objRoles = new Roles();
    }

    /**
     * Get role list to show in grid
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function roleList(Request $request)
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
                    'message' => $validator->messages()->all(),
                ], 200);
            }

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            $searchByKeyword = (isset($request->searchByKeyword) && !empty($request->searchByKeyword)) ? $request->searchByKeyword : null;

            // Total Count
            $totalCount = Roles::getRoleCount($searchByKeyword);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            $roleList =  Roles::getRoleList($request->limit, $getPaginationData['offset'], $sort, $order, $searchByKeyword);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'data' => [
                    'roleList' => $roleList,
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
     * Add or Update Role.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrUpdateRole(Request $request)
    {
        try {
            $rule = [
                'name' => 'required|max:100',
                'slug' => 'required|max:100|unique:roles'
            ];

            if (isset($request->id) && $request->id > 0) {
                $rule['id'] = 'required|integer|min:1';
                $rule['slug'] = 'required|max:100|unique:roles,slug,'.$request->id;
            }

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all(),
                ], 200);
            }

            // Save Roles data
            $postedData = $request->only('name', 'slug');

            if (isset($request->id) && $request->id > 0) {
                $postedData['id'] = $request->id;
            }

            DB::beginTransaction();
            $savedData = $this->objRoles->insertUpdate($postedData);

            if ($savedData) {
                DB::commit();

                $msg = (isset($request->id) && $request->id > 0) ? trans('apimessages.role_updated_suceessfully') : trans('apimessages.role_added_suceessfully');
                return response()->json([
                    'status' => 1,
                    'message' => $msg,
                    'data' => [
                        'role' => $savedData,
                    ],
                ], 200);
            } else {
                DB::rollback();
                $errorMsg = (isset($request->id) && $request->id > 0) ? trans('apimessages.error_updating_role') : trans('apimessages.error_adding_role');
                return response()->json([
                    'status' => 0,
                    'message' => $errorMsg,
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete Role data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRole(Request $request, $id) {
        $role = $this->objRoles->where('id', $request->id)->first();

        try {
            if ($role === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.role_not_found'),
                ], 404);
            } else {
                DB::beginTransaction();
                $role->delete();
                DB::commit();
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.role_deleted_successfully'),
                ]);
            }
        } catch (\Exception $e) {
            DB::rollback();
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
     * Retrive role by id, returns only active role.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoleById(Request $request) {
        $rule = [
            'id' => 'required|integer|min:1'
        ];

        $validator = Validator::make($request->all(), $rule);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->messages()->all()[0],
            ], 200);
        }

        $role = $this->objRoles->where('id', $request->id)->first();
        try {
            if ($role === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.role_not_found'),
                ], 404);
            } else {
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.default_success_msg'),
                    'data' => $role
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
    
    public function roles()
    {
        try{ 
            $roles = Roles::get(['id','name', 'slug']);
            if(!$roles){
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.role_not_found'),
                ], 404);
            }
            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $roles
            ]);

        } catch(\Expection $e){
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
