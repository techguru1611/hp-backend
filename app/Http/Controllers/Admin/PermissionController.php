<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Log;
use Validator;
use Helpers;
use App\Permission;
use App\RolePermission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->objPermission = new Permission();
        $this->objRolePermission = new RolePermission();
    }

    /**
     * Get permission list to show in grid
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissionList(Request $request)
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

            // Sorting
            $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
            $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

            $searchByKeyword = (isset($request->searchByKeyword) && !empty($request->searchByKeyword)) ? $request->searchByKeyword : null;

            // Total Count
            $totalCount = Permission::getPermissionCount($searchByKeyword);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            $permissionList =  Permission::getPermissionList($request->limit, $getPaginationData['offset'], $sort, $order, $searchByKeyword);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'totalCount' => $totalCount,
                'noOfPages' => $getPaginationData['noOfPages'],
                'next' => $getPaginationData['next'],
                'previous' => $getPaginationData['previous'],
                'data' => [
                    'permissionList' => $permissionList,
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
     * add or Update permission data.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrUpdatePermission(Request $request)
    {
        try {
            $rule = [
                'name' => 'required|max:100',
                'slug' => 'required|max:100|regex:/^[A-Za-z_]+$/|unique:permissions'
            ];

            if (isset($request->id) && $request->id > 0) {
                $rule['id'] = 'required|integer|min:1';
                $rule['slug'] = 'required|max:100|unique:permissions,slug,'.$request->id;
            }

            $messages['slug.regex'] = trans('apimessages.ONLY_CHARACTER_WITH_UNDERSCORE_ALLOWED');

            $validator = Validator::make($request->all(), $rule, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all(),
                ], 200);
            }

            // Save Permission data
            $postedData = $request->only('name', 'slug');
            $postedData["created_by"] = $request->user()->id ;

            if (isset($request->id) && $request->id > 0) {
                $postedData['id'] = $request->id;
                $postedData["updated_by"] = $request->user()->id;
            }

            DB::beginTransaction();
            $savedData = $this->objPermission->insertUpdate($postedData);

            if ($savedData) {
                DB::commit();

                $msg = (isset($request->id) && $request->id > 0) ? trans('apimessages.permission_updated_suceessfully') : trans('apimessages.permission_added_suceessfully');
                return response()->json([
                    'status' => 1,
                    'message' => $msg,
                    'data' => [
                        'permission' => $savedData,
                    ],
                ], 200);
            } else {
                DB::rollback();
                $errorMsg = (isset($request->id) && $request->id > 0) ? trans('apimessages.error_updating_permission') : trans('apimessages.error_adding_permission');
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
     * Delete permission data
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePermission(Request $request, $id) {
        $permission = $this->objPermission->where('id', $request->id)->first();

        try {
            if ($permission === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.permission_not_found'),
                ], 404);
            } else {
                DB::beginTransaction();
                $permission->delete();
                DB::commit();
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.permission_deleted_successfully'),
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
     * Retrive permission by id, returns only active permission.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPermissionById(Request $request) {
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

        $permission = $this->objPermission->where('id', $request->id)->first();
        try {
            if ($permission === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.permission_not_found'),
                ], 404);
            } else {
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.default_success_msg'),
                    'data' => $permission
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

    /**
     * Assign Role to permission.,
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignPermissoinToRole(Request $request)
    {
        try {
            $rule = [
                'role_id' => 'required|integer|exists:roles,id',
                'permission_id.*' => 'required|integer|exists:permissions,id',
                'is_allowed.*' => 'required|integer'
            ];

            $validator = Validator::make($request->all(), $rule);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all(),
                ], 200);
            }

            // Save Permission data
            $role_id = $request->get('role_id');
            $pCount = count($request->get('permission_id'));
            $permission_id = $request->get('permission_id');
            $is_allowed = $request->get('is_allowed');
            
            DB::beginTransaction();
            for($rIndex = 0; $rIndex < $pCount; $rIndex++) {
                $postedData = [
                    "role_id" => $role_id,
                    "permission_id" => $permission_id[$rIndex],
                    "is_allowed" => isset($is_allowed[$rIndex]) ? $is_allowed[$rIndex] : 0,
                    "created_by" => $request->user()->id,
                    "updated_by" => $request->user()->id
                ];
                $savedData = $this->objRolePermission->insertUpdate($postedData);
            }
            DB::commit();

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.assign_permission_suceessfully'),
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.error_assigning_permission'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retrive permission by id, returns only active permission.
     *
     * @param Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPermissionByRoleId(Request $request) {
        $rule = [
            'role_id' => 'required|integer|exists:roles,id'
        ];

        $validator = Validator::make($request->all(), $rule);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->messages()->all(),
            ], 200);
        }

        $rolePermission = $this->objRolePermission->getPermissionByRoleId($request->role_id);
        try {
            if ($rolePermission === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.permission_not_found'),
                ], 404);
            } else {
                return response()->json([
                    'status' => 1,
                    'message' => trans('apimessages.default_success_msg'),
                    'data' => $rolePermission
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