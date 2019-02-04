<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Validator;
use Config;
use DB;
use App\User;
use App\KycDocument;
use App\Helpers\ImageUpload;
use Carbon\Carbon;

class KycDocumentController extends Controller
{

    protected $objKycDocument = null;
    protected $kycOriginalDocumentUploadPath;
    protected $kycOriginalDocumentGetUploadPath;

    public function __construct()
    {
        $this->objKycDocument = new KycDocument();

        $this->kycOriginalDocumentUploadPath = Config::get('constant.USER_KYC_DOCUMENT_UPLOAD_PATH');
        $this->kycOriginalDocumentGetUploadPath = Config::get('constant.USER_KYC_DOCUMENT_GET_UPLOAD_PATH');
    }

    /**
     * Retrive list of all uploaded documents for requested user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getUserDocumentList(Request $request)
    {
        try {
            $documentTypeArray = Config::get('constant.DOCUMENT_TYPE_ARRAY');
            $documentType = [];
            foreach ($documentTypeArray as $item) {
                $documentType[$item["id"]] = $item["name"];
            }

            $documents = $this->objKycDocument->getDocumentByUserId($request->user()->id);

            foreach ($documents as $document) {
                $document->file_name = url($this->kycOriginalDocumentGetUploadPath . $document->file_name);
                if ($document->document_type != "") {
                    $document->document_type_name = $documentType[$document->document_type];
                } else {
                    $document->document_type_name = "";
                }
            }

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'data' => $documents
            ], 200);
        } catch (\Exception $e) {
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'error' => $e->getMessage() . ' = ' . $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Add new KYC document for logged in user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addMultipleFile(Request $request)
    {
        $docImageName = [];

        try {
            $rules = [
                'document_type.*' => 'required'
            ];

            $validationMessage = [
                'document_type.*.required' => trans("apimessages.KYC_DOCUMENT_TYPE_VALIDATION")
            ];
            $totalDocuments = count($request->get('document_type')) - 1;
            $kycData = [];

            foreach (range(0, $totalDocuments) as $index) {
                $rules['files.' . $index] = 'required|mimes:pdf,png,jpeg,jpg,bmp,gif|max:5120';
                $validationMessage["files.$index.required"] = trans("apimessages.KYC_DOCUMENT_REQUIRED_FILE_VALIDATION");
                $validationMessage["files.$index.mimes"] = trans("apimessages.KYC_DOCUMENT_ALLOWED_FILE_VALIDATION");
                $validationMessage["files.$index.max"] = trans("apimessages.KYC_DOCUMENT_MAX_SIZE_FILE_VALIDATION");
            }

            $validator = Validator::make($request->all(), $rules, $validationMessage);

            if ($validator->fails()) {
                $messages = array_values(array_unique($validator->messages()->all()));
                return response()->json([
                    'status' => 0,
                    'message' => $messages
                ], 400);
            }

            $userId = $request->user()->id;

            DB::beginTransaction();
            for ($rIndex = 0; $rIndex <= $totalDocuments; $rIndex++) {              
                // Upload Document or Image
                if (!empty($request->file('files')[$rIndex]) && $request->file('files')[$rIndex]->isValid()) {
                    $docFile = $request->file('files')[$rIndex];
                    $mimeType = $docFile->getMimeType();

                    /**
                     * We like to create centralize upload system which helps to change the FILESYSTEM via .env file.
                     * By default everything will goes to LOCALSTORAGE,
                     */
                    $fileName = str_random(20) . '.' . $docFile->getClientOriginalExtension();
                    $filePath = \Storage::putFileAs($this->kycOriginalDocumentUploadPath, ($docFile), $fileName);
                    \Storage::setVisibility($filePath, 'public');
                    $docImageName[] = $filePath;

                    $kycData = [
                        'user_id' => $userId,
                        'file_name' => $fileName,
                        'document_type' => $request->get('document_type')[$rIndex],
                        'mime_type' => $mimeType
                    ];
                    KycDocument::create($kycData);
                }
            }
            $user = User::find($userId);
            /** 0 = Pending | 1 = Uploaded or Submitted | 2 = Rejected | 3 = Corrrection | 4 = Approved or completed  */
            $user->kyc_status = 1;
            $user->kyc_comment = null;
            /**
             *  This field helps to identify that user have uploaded document or not.
             *  0 = Pending : Did not uploaded yet.
             *  1 = Submitted : Document uploaded
             *  2 = Completed/Approved : Documents are approved.
             */
            if ($user->user_kyc_status == 1) {
                $user->user_kyc_status = 1;
                $user->kyc_uploaded_at = Carbon::now();
            }
            $user->save();

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.KYC_DOCUMENT_ADDED_SUCCESS_MESSAGE'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            foreach ($docImageName as $file) {
                if (\Storage::exists($file)) {
                    \Storage::delete($file);
                }
            }

            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg')
            ], 500);
        }
    }

    /**
     * Add new KYC document for logged in user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addSingleFile(Request $request)
    {
        $docImageName = [];

        try {
            $rules = [
                'document_type' => 'required',
                'files' => 'required|mimes:pdf,png,jpeg,jpg,bmp,gif|max:5120',
            ];

            $validationMessage = [
                'document_type.required' => trans("apimessages.KYC_DOCUMENT_TYPE_VALIDATION"),
                'files.required' => trans("apimessages.KYC_DOCUMENT_REQUIRED_FILE_VALIDATION"),
                'files.mimes' => trans("apimessages.KYC_DOCUMENT_ALLOWED_FILE_VALIDATION"),
                'files.max' => trans("apimessages.KYC_DOCUMENT_MAX_SIZE_FILE_VALIDATION")
            ];

            $validator = Validator::make($request->all(), $rules, $validationMessage);

            if ($validator->fails()) {
                $messages = array_values(array_unique($validator->messages()->all()));
                return response()->json([
                    'status' => 0,
                    'message' => $messages
                ], 400);
            }
            $userId = $request->user()->id;

            DB::beginTransaction();
            // Upload Document or Image
            if (!empty($request->file('files')) && $request->file('files')->isValid()) {
                $docFile = $request->file('files');
                $mimeType = $docFile->getMimeType();
                $params = [
                    'originalPath' => public_path($this->kycOriginalDocumentUploadPath),
                    'previousImage' => ''
                ];

                /**
                 * We like to create centralize upload system which helps to change the FILESYSTEM via .env file.
                 * By default everything will goes to LOCALSTORAGE,
                 */
                $fileName = str_random(20) . '.' . $docFile->getClientOriginalExtension();
                $filePath = \Storage::putFileAs($this->kycOriginalDocumentUploadPath, ($docFile), $fileName);
                \Storage::setVisibility($filePath, 'public');
                $docImageName[] = $filePath;

                $kycData = [
                    'user_id' => $userId,
                    'file_name' => $fileName,
                    'document_type' => $request->get('document_type'),
                    'mime_type' => $mimeType
                ];
                $kyc = KycDocument::create($kycData);
            }

            $user = User::find($userId);
            /** 0 = Pending | 1 = Uploaded or Submitted | 2 = Rejected | 3 = Corrrection | 4 = Approved or completed  */
            $user->kyc_status = 1;
            $user->kyc_comment = null;
            /**
             *  This field helps to identify that user have uploaded document or not.
             *  0 = Pending : Did not uploaded yet.
             *  1 = Submitted : Document uploaded
             *  2 = Completed/Approved : Documents are approved.
             */
            if ($user->user_kyc_status == 1) {
                $user->user_kyc_status = 1;
                $user->kyc_uploaded_at = Carbon::now();
            }
            $user->save();

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.KYC_DOCUMENT_ADDED_SUCCESS_MESSAGE'),
                'data' => [
                    'uplodad_file' => url($this->kycOriginalDocumentGetUploadPath . $fileName),
                    'id' => $kyc->id
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            foreach ($docImageName as $file) {
                if (\Storage::exists($file)) {
                    \Storage::delete($file);
                }
            }
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));

            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'error' => $e->getMessage() . ' = ' . $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Delete the document for logged in user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id of the requested document
     * @return \Illuminate\Http\Response
     */
    public function deleteDocument(Request $request, $id)
    {
        try {

            $userId = $request->user()->id;
            $kycDocument = $this->objKycDocument->getDocumentById($userId, $id);

            DB::beginTransaction();
            if ($kycDocument === null) {
                return response()->json([
                    'status' => 0,
                    'message' => trans('apimessages.KYC_DOCUMENT_NOT_FOUND'),
                ], 404);
            } else {
                $kycDocument->delete();
                $docCount = $this->objKycDocument->getDocumentCountByUserId($userId);
                /**
                 * Checking one record becuase the transaction is not committed to database that's why checking againts one record
                 *  This field helps to identify that user have uploaded document or not.
                 *  0 = Pending : Did not uploaded yet.
                 *  1 = Submitted : Document uploaded
                 *  2 = Completed/Approved : Documents are approved.
                 */
                if ($docCount == 1) {
                    $request->user()->user_kyc_status = 0;
                    $request->user()->kyc_status = 0;
                    $request->user()->kyc_comment = Config::get('constant.PENDING_KYC_COMMENT');
                    $request->user()->kyc_uploaded_at = null;
                    $request->user()->save();
                }
            }
            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.KYC_DOCUMENT_DELETED_SUCCESS_MESSAGE')
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'error' => $e->getMessage() . ' = ' . $e->getTraceAsString()
            ], 500);
        }
    }
}
