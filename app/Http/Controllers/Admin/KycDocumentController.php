<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;
use Validator;
use Config;
use Helpers;
use App\User;
use App\KycDocument;
use App\KycDocumentComment;

/**
 * Controller will manage KYC document. It holds the methods for Reject/Approve user/agent KYC document.
 * Retrive user/agent KYC document.
 */
class KycDocumentController extends Controller
{
    
    protected $objKycDocument = null;
    protected $kycOriginalDocumentUploadPath;
    protected $kycOriginalDocumentGetUploadPath;

    public function __construct(){
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
    public function getKYCDocumentByUserId(Request $request){
        try {
            $documentTypeArray = Config::get('constant.DOCUMENT_TYPE_ARRAY');
            $documentType  = [];
            foreach($documentTypeArray as $item) {
                $documentType[$item["id"]] = $item["name"];
            }

            $documents = $this->objKycDocument->getDocumentByUserIdForAdmin($request->user_id);

            foreach($documents as $document) {
                $document->file_name = url($this->kycOriginalDocumentGetUploadPath . $document->file_name);
                if($document->document_type != "") {
                    $document->document_type_name = $documentType[$document->document_type];
                } else {
                    $document->document_type_name = "";
                }                
            }

            $lastComment = $this->objKycDocument->getLastCommentOnDocumentById($request->user_id);
            $lastComment = (isset($lastComment) && $lastComment !== null ? [$lastComment] : []);

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.default_success_msg'),
                'last_comment' =>  $lastComment,
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
     * Update the status of the KYC document for the selected user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateKYCStatus(Request $request)
    {
        $docImageName = [];

        try {
            $rules = [
                'kyc_status' => 'required|integer',
                'user_id' => 'required|integer',
            ];

            /**
             * 0 = Pending
             * 1 = Uploaded or Submitted
             * 2 = Rejected 
             * 3 = Correction
             * 4 = Approved or completed 
             */
            /** Verify that admin rejected the profile or not if it's rejected than adding validation for comments/notes  */
            if(isset($request->kyc_status) && ($request->kyc_status == Config::get('constant.KYC_REJECTED_STATUS') || $request->kyc_status == Config::get('constant.KYC_CORRECTION_STATUS'))) {
                $rules['comments'] = 'required';
            }
            
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()
                ], 400);
            }

            $kyc_id = KycDocument::where('user_id', '=', $request->user_id)->select('id')->first();
            
            DB::beginTransaction();
            /**
             * Storing comments/reason why user/agent profile was rejected.
             * 0 = Pending | 1 = Uploaded or Submitted | 2 = Rejected | 3 = Corrrection | 4 = Approved or completed 
             */
            if($request->kyc_status == Config::get('constant.KYC_REJECTED_STATUS') || $request->kyc_status == Config::get('constant.KYC_CORRECTION_STATUS')) {                
                $kycDocComment = [
                    'user_id' => $request->user_id,
                    'notes' => $request->comments,
                    'notes_by' => $request->user()->id                    
                ];
                KycDocumentComment::create($kycDocComment);
            }

            $updateKYCUpdatedDate = ['updated_at' => Carbon::now()];
            KycDocument::where('user_id', '=', $request->user_id)->update($updateKYCUpdatedDate);

            /**
             * Updating KYC Status, Storing reference of admin who processed the KYC documents
             */
            $user = User::find($request->user_id);
            $user->kyc_status = $request->kyc_status;
            $user->kyc_approved_by = $request->user()->id;
            $user->kyc_approved_at = Carbon::now();

            $user->kyc_comment = null;
            // If admin updated user KYC status to pending or rejected or correction
            if ($request->kyc_status == Config::get('constant.USER_KYC_PENDING_STATUS')) {
                $user->kyc_comment = Config::get('constant.PENDING_KYC_COMMENT');
            } else if ($request->kyc_status == Config::get('constant.USER_KYC_REJECTED_STATUS') || $request->kyc_status == Config::get('constant.USER_KYC_CORRECTION_STATUS')) {
                $user->kyc_comment = (isset($request->comments) && !empty($request->comments)) ? $request->comments : null;
            }

            /**
             *  This field helps to identify that user have uploaded document or not.
             *  0 = Pending : Did not uploaded yet.
             *  1 = Submitted : Document uploaded
             *  2 = Completed/Approved : Documents are approved.
             */
            if($request->kyc_status == Config::get('constant.KYC_APPROVED_STATUS')) {
                $user->user_kyc_status = 2;
                $user->kyc_uploaded_at = Carbon::now();
            }
            $user->save();
            
            /**
             * Notifing user that his/her profile is approved by Admin.
             */
            if($request->kyc_status == Config::get('constant.KYC_APPROVED_STATUS')) {
                $approvedMessageText = trans('apimessages.KYC_DOCUMENT_APPROVED_SUCCESSFULLY_MESSAGE');
                $sendApprovedMessage = Helpers::sendMessage($user->mobile_number, $approvedMessageText);

                if (!$sendApprovedMessage) {
                    Log::error(strtr(trans('log_messages.message_error'),[
                        '<Mobile Number>' => $user->mobile_number
                    ]));
                    DB::rollback();
                    return response()->json([
                        'status' => '0',
                        'message' => trans('apimessages.something_went_wrong'),
                    ], 400);
                }

                $data = [];
                $data['customerName'] = $user->full_name;
                $subject = trans("apimessages.KYC_DOCUMENT_APPROVED_SUCCESSFULLY_EMAIL_SUBJECT");
                $template = 'kycDocumentApproved';

                // Helpers::sendMail($user->email, $subject, $template, $data);
            } else {
                /**
                 * Notifing user that his/her profile was rejected by admin.
                 */
                $rejectedMessageText = trans('apimessages.KYC_DOCUMENT_REJECTED_SMS_MESSAGE');
                $sendRejectedMessage = Helpers::sendMessage($user->mobile_number, $rejectedMessageText);

                if (!$sendRejectedMessage) {
                    DB::rollback();
                    Log::error(strtr(trans('log_messages.message_error'),[
                        '<Mobile Number>' => $user->mobile_number
                    ]));
                    return response()->json([
                        'status' => '0',
                        'message' => trans('apimessages.something_went_wrong'),
                    ], 400);
                }

                $data = [];
                $data['customerName'] = $user->full_name;
                $data['comments'] = $request->comments;
                $subject = trans("apimessages.KYC_DOCUMENT_REJECTED_EMAIL_SUBJECT");
                $template = 'kycDocumentRejected';

                // Helpers::sendMail($user->email, $subject, $template, $data);
            }

            DB::commit();
            Log::info(strtr(trans('log_messages.updateKYC'),[
                '<User>' => $request->user()->mobile_number
            ]));

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.KYC_DOCUMENT_STATUS_SUCCESS_MESSAGE'),
            ], 200);            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error(strtr(trans('log_messages.default_error_msg'),[
                '<Message>' => $e->getMessage()
            ]));
            return response()->json([
                'status' => 0,
                'message' => trans('apimessages.default_error_msg'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload KYC document from admin Panel
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addMultipleFile(Request $request)
    {
        $docImageName = [];

        try {
            $rules = [
                'user_id' => 'required',
                'document_type.*' => 'required'
            ];
        
            $validationMessage = [
                'document_type.*.required' => trans("apimessages.KYC_DOCUMENT_TYPE_VALIDATION")
            ];          
            $totalDocuments = count($request->get('document_type')) - 1;
            $kycData = [];

            foreach(range(0, $totalDocuments) as $index) {
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

            $userId = $request->user_id;

            DB::beginTransaction();
            for($rIndex = 0; $rIndex <= $totalDocuments; $rIndex++) {              
                // Upload Document or Image
                if (!empty($request->file('files')[$rIndex]) && $request->file('files')[$rIndex]->isValid()) 
                {
                    $docFile = $request->file('files')[$rIndex];
                    $mimeType = $docFile->getMimeType();
                
                    /**
                     * We like to create centralize upload system which helps to change the FILESYSTEM via .env file.
                     * By default everything will goes to LOCALSTORAGE,
                     */
                    $fileName = str_random(20). '.' . $docFile->getClientOriginalExtension();                          
                    $filePath = \Storage::putFileAs($this->kycOriginalDocumentUploadPath, ($docFile), $fileName);                 
                    \Storage::setVisibility($filePath, 'public');
                    $docImageName[] = $filePath;
                    
                    $kycData =  [
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
            Log::info(strtr(trans('log_messages.KYC_document_add'),[
                '<User>' => $user->mobile_number
            ]));

            return response()->json([
                'status' => 1,
                'message' => trans('apimessages.KYC_DOCUMENT_ADDED_SUCCESS_MESSAGE_ADMIN'),
            ], 200);            
        } catch (\Exception $e) {
            DB::rollback();

            foreach($docImageName as $file) {
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
     * Retrive list of all user who's KYC document list is pending.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getPendingKYCDocumentList(Request $request){
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
            $totalCount = $this->objKycDocument->getPendingKYCDocumentCount($request);

            // Get offset from page number
            $getPaginationData = Helpers::getAPIPaginationData($request->page, $request->limit, $totalCount);

            // Contact us data
            $requestData =  $this->objKycDocument->getPendingKYCDocumentList($request, $request->limit, $getPaginationData['offset']);

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
     * Delete the document for logged in user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id of the requested document
     * @return \Illuminate\Http\Response
     */
    public function deleteDocument(Request $request)
    {
        try {
            $rules = [
                'user_id' => 'required',
                'doc_id' => 'required'
            ];
                     
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 0,
                    'message' => $validator->messages()->all()
                ], 400);
            }
            
            $userId = $request->user_id;
            $docId = $request->doc_id;
            $kycDocument = $this->objKycDocument->getDocumentById($userId, $docId);
        
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
                 */
                if($docCount == 1) {
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
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
