<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class UserBeneficiary extends Model
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_beneficiaries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'name', 'nick_name', 'mobile_number', 'account_number', 'ifsc_code', 'swift_code', 'branch_code', 'bank_name', 'branch_name', 'address', 'country', 'state', 'city', 'otp', 'otp_date', 'otp_created_date', 'verification_status'
    ];
    
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
     * Validation Rules
     */
    /*public static function getRules($request) {
         $rules = [
            /** Personal Details
            'first_name'        => 'required|max:50',
            'last_name'         => 'required|max:50',
            'email'             => 'required|email',
            'country_code'      => 'required',
            'phone'             => 'required',
            'relationship'      => 'required',

            /*** Bank Account Details
            'account_type'      => 'required',
            'issuer_code'       => 'required',
            'bank_name'         => 'required|max:100',
            'branch_name'       => 'required|max:50',
            'swift_code'        => 'max:50',
            'ifsc_code'         => 'max:50',
            'branch_code'       => 'max:50',

            'address'           => 'required|max:255',
            'country'           => 'required',
            'state'             => 'required',
            'city'              => 'required',
            
            'account_no'        => 'required|confirmed',
             'account_no_confirmation' => 'required'
        ];

        if(isset($request->id)) {
            $rules["bank_detail_id"] = 'required';
        }

        return $rules;
    }*/

    /**
     * Validation Messages
     */
    static public $messages = [];

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
    /*public function bankDetails()
    {
        return $this->hasMany(UserBeneficiaryBankDetail::class, 'beneficiary_id' ,'id');
    }*/
    
    /**
     * Add/Update the selected bank detail
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && !empty($data['id']) && $data['id'] > 0) {

            $UserBeneficiary = UserBeneficiary::find($data['id']);

            $UserBeneficiary->update($data);

            return UserBeneficiary::find($data['id']);
        } else {        
            return UserBeneficiary::create($data);
        }
    }

    /**
     * Store the comment for documents.
     */
    public function removeBeneficiary($beneficiaryId)
    {
        $returnResponse = false;
        $userBeneficiary = UserBeneficiary::find($beneficiaryId);
        
        if($userBeneficiary) {
            //$userBeneficiary->bankDetails()->delete();
            $userBeneficiary->delete();
            $returnResponse = true;
        }

        return $returnResponse;
    }

    public function findById($userId, $beneficiaryId)
    {
        return UserBeneficiary::where('user_id', '=', $userId)
                            ->select('id','name','nick_name','account_number','mobile_number','swift_code','bank_name','ifsc_code','branch_code','branch_name','address','country','state','city')
                            ->where('id', '=', $beneficiaryId)
                            ->first();
    }
    
    /**
     * Find by Id
     */
    public function countBeneficiaryBank($userId)
    {
        return UserBeneficiary::join('user_beneficiary_bank_details', 'user_beneficiary_bank_details.beneficiary_id', 'user_beneficiaries.id')
                    ->where('user_id', '=', $userId)
                    ->count();
    }

    /**
     * Find by Id
     */
    public function getBankDetails($userId, $beneficiaryId, $bankDetailId)
    {
        return UserBeneficiary::whereHas('bankDetails', function($query) use($bankDetailId) {
                                $query->where('id', '=', $bankDetailId);
                        })
                        ->with('bankDetails')
                        ->where('user_id', '=', $userId)
                        ->where('id', '=', $beneficiaryId)
                        ->first();
    }

    /**
     * Get All Beneficiary with All data.
     */
    public function getAllWithPagingForAdmin($limit, $offset, $sort, $order) 
    {
        return UserBeneficiary::with(['user:id,full_name'])
                    ->with('bankDetails')
                    ->whereHas('bankDetails')
                    ->orderBy($sort, $order)
                    ->orderBy('id', 'DESC')
                    ->take($limit)
                    ->offset($offset)
                    ->get();
    }

    /**
     * Get All Beneficiary with count.
     */
    public function getCountForAdmin() 
    {
        return UserBeneficiary::whereHas('bankDetails')->count();
    }

    public static function countTotalUserBeneficiary($user_id){
        return UserBeneficiary::where('user_id',$user_id)->count();
    }

    public static function getAllUserBeneficiary($limit, $offset, $sort, $order, $user_id){
        return UserBeneficiary::where('user_id',$user_id)
            ->orderBy($sort, $order)
            ->orderBy('id', 'DESC')
            ->take($limit)
            ->offset($offset)
            ->get(['id','name','nick_name','mobile_number','account_number','bank_name']);
    }

}