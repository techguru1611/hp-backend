<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use DB;
use Config;
use Helpers;

class KycDocument extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'kyc_documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'document_type', 'file_name', 'mime_type'];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Store the reference of document owner
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Store the comment for documents.
     */
    public function comments()
    {
        return $this->hasMany(KycDocumentComment::class, 'kyc_id' ,'id');
    }

    /**
     * getDocumentByUserId() Function returns the requested user's KYC documents.
     * 
     * Request @param: $userId - Must be valid user id
     * Response: Returs the KYC document object with the Comments posted by Admin. 
     */
    public function getDocumentByUserId($userId) {
        return KycDocument::where('user_id', '=', $userId)
                        ->orderBy('created_at', 'DESC')
                        ->select('id',
                                'document_type', 
                                'file_name', 
                                'mime_type', 
                                DB::raw("'' AS document_type_name"),
                                'created_at',
                                'updated_at'
                        )
                        ->get();

        /** We are no longer maintaining document to document comments. */
        /*
        return KycDocument::with(['comments' => function($query) {
                        $query->select('kyc_id','notes','created_at');
                        $query->orderBy('created_at');
                        // $query->limit(1);
                    }])     
                    ->where('user_id', '=', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->select('id',
                            'document_type', 
                            'file_name', 
                            'mime_type', 
                            DB::raw("'' AS document_type_name"),
                            'created_at',
                            'updated_at'
                    )
                    ->get();
        */
    }

    /**
     * getDocumentByUserIdForAdmin() Function returns the requested user's KYC documents.
     * 
     * Request @param: $userId - Must be valid user id
     * Response: Returs the KYC document object with the Comments posted by Admin. 
     */
    public function getDocumentByUserIdForAdmin($userId) {
        return KycDocument::where('user_id', '=', $userId)
                    ->orderBy('kyc_documents.created_at', 'DESC')
                    ->select('*', DB::raw("'' AS document_type_name"))
                    ->get();
        /** No Longer maintaining document to document comments */
        /*
        return KycDocument::with(['comments', "comments.notesBy:id,full_name"])
                    ->where('user_id', '=', $userId)
                    ->orderBy('kyc_documents.created_at', 'DESC')
                    ->select('*', DB::raw("'' AS document_type_name"))
                    ->get();
        */
    }

    /**
     * getPendingKYCDocumentList() Function returns the list of user's whoes KYC documents verification is pending.
     * 
     * Request @param: $request - Request object with short and search parametes.
     * Request @param: $limit - No of records.
     * Request @param: $offset - Offest for pagination.
     * Response: Returs the List of user object.
     */
    public function getPendingKYCDocumentList($request, $limit, $offset) {

        $sort = (isset($request->_sort) && !empty($request->_sort)) ? $request->_sort : 'id';
        $order = (isset($request->_order) && !empty($request->_order)) ? $request->_order : 'DESC';

        $searchByFullName = (isset($request->full_name_like) && !empty($request->full_name_like)) ? $request->full_name_like : null;
        $searchByMobileNumber = (isset($request->mobile_number_like) && !empty($request->mobile_number_like)) ? $request->mobile_number_like : null;
        $searchByKYCStatus = (isset($request->kyc_status_like) && !empty($request->kyc_status_like)) ? $request->kyc_status_like : null;

        /** 1 : Submitted | 3 : Corrections */
        $userQuery = User::whereHas('kycDocument')->whereIn('kyc_status', [1,3]);

        // Search by Full name
        if ($searchByFullName !== null) {
            $userQuery = $userQuery->where('full_name', 'LIKE', "%$searchByFullName%");
        }
        
        // Search by Mobile number
        if ($searchByMobileNumber !== null) {
            $userQuery = $userQuery->where('mobile_number', 'LIKE', "%$searchByMobileNumber%");
        }
        
        // Search by Browser
        // Search by KYC document status
        if ($searchByKYCStatus !== null) {
            /* 0 - Pending | 1 = Approved | 2 = Rejected | 3 = Correction */
            $searchStatus = Helpers::getKYCDocumentStatusIdFromName($searchByKYCStatus);
            $userQuery = $userQuery->where('kyc_status', '=', $searchStatus);
        }

        $userQuery->orderBy($sort, $order);        
            $userQuery->select('id', 'full_name', 'mobile_number', 'email',
                DB::raw('CASE WHEN kyc_status=' . Config::get('constant.KYC_PENDING_STATUS') . ' THEN "' . Config::get('constant.KYC_PENDING') . 
                    '" WHEN kyc_status=' . Config::get('constant.KYC_APPROVED_STATUS') . ' THEN "' . Config::get('constant.KYC_APPROVED') . 
                    '" WHEN kyc_status=' . Config::get('constant.KYC_REJECTED_STATUS') . ' THEN "' . Config::get('constant.KYC_REJECTED') . 
                    '" WHEN kyc_status=' . Config::get('constant.KYC_CORRECTION_STATUS') . ' THEN "' . Config::get('constant.KYC_CORRECTION') . 
                    '" ELSE "' . Config::get('constant.KYC_PENDING') . '" END as kyc_status'),
                    'created_at', 'updated_at', 'last_activity_at'
                );

        return $userQuery->take($limit)
                ->offset($offset)
                ->get();
    }

    /**
     * getPendingKYCDocumentCount() Function returns the count of user whoes KYC document verification is pending.
     * 
     * Request @param: $request - Request object with short and search parametes.
     * Response: Returs the List of user object.
     */
    public function getPendingKYCDocumentCount($request) {
        $userQuery = User::whereHas('kycDocument')->whereIn('kyc_status', [0,3]);

        $searchByFullName = (isset($request->full_name_like) && !empty($request->full_name_like)) ? $request->full_name_like : null;
        $searchByMobileNumber = (isset($request->mobile_number_like) && !empty($request->mobile_number_like)) ? $request->mobile_number_like : null;
        $searchByKYCStatus = (isset($request->kyc_status_like) && !empty($request->kyc_status_like)) ? $request->kyc_status_like : null;

        $userQuery = User::whereHas('kycDocument')->whereIn('kyc_status', [0,3]);

        // Search by Full name
        if ($searchByFullName !== null) {
            $userQuery = $userQuery->where('full_name', 'LIKE', "%$searchByFullName%");
        }
        
        // Search by Mobile number
        if ($searchByMobileNumber !== null) {
            $userQuery = $userQuery->where('mobile_number', 'LIKE', "%$searchByMobileNumber%");
        }
        
        // Search by Browser
        if ($searchByKYCStatus !== null) {
            /* 0 - Pending | 1 = Approved | 2 = Rejected | 3 = Correction */
            $searchStatus = Helpers::getKYCDocumentStatusIdFromName($searchByKYCStatus);
            $userQuery = $userQuery->where('kyc_status', '=', $searchStatus);
        }

        return $userQuery->count();
    }

     /**
     * Retrive the single document for logged in user.
     * 
     * @param : UserId : Logged in user ID
     * @param : ID : document to be fetched.
     */
    public function getDocumentById($userId, $id) {
        return KycDocument::where('user_id', '=', $userId)
                    ->where('id', '=', $id)
                    ->first();
    }

    /**
     * Retrive the last Comment on document
     * 
     * @param : UserId : Logged in user ID
     */
    public function getLastCommentOnDocumentById($userId) {
        return KycDocumentComment::where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->select('id', 'notes', 'created_at')
                ->first();        
    }

     /**
     * Get count of total number of document uploaded by user.
     * 
     * @param : UserId : Logged in user ID
     */
    public function getDocumentCountByUserId($userId) {
        return KycDocument::where('user_id', '=', $userId)->count();
    }
}
