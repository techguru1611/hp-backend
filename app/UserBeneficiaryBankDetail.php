<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserBeneficiaryBankDetail extends Model
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_beneficiary_bank_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['beneficiary_id', 'account_type', 'identification_type', 'issuer_code', 
            'bank_name', 'branch_name', 'swift_code', 'ifsc_code', 'branch_code', 
            'address', 'country', 'state', 'city', 'account_no',
            'otp', 'otp_date', 'otp_created_date', 'verification_status', 'is_primary'];
    
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
    public function userBeneficiary()
    {
        return $this->belongsTo(UserBeneficiary::class, 'beneficiary_id');
    }

    /**
     * Delete all the bank details belongs to selected user.
     */
    public function removeBeneficiary($beneficiaryId)
    {
        return UserBeneficiaryBankDetail::where('beneficiary_id', '=', $beneficiaryId)->delete();
    }

    /**
     * Add/Update the selected bank detail
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && !empty($data['id']) && $data['id'] > 0) {

            $userBeneficiaryBankDetail = UserBeneficiaryBankDetail::find($data['id']);

            $userBeneficiaryBankDetail->update($data);

            return UserBeneficiaryBankDetail::find($data['id']);
        } else {        
            return UserBeneficiaryBankDetail::create($data);
        }
    }
    
    /**
     * Find by Id
     */
    public function findById($Id)
    {
        return UserBeneficiaryBankDetail::find($beneficiaryId);
    }

    /**
     * Mark bank detail as Primary
     * 
     * @param 
     *      beneficiaryId :  Beneficiary Id of the user
     *      bankDetailId :  Bank detail Id of user's bank account
     */
    public function markAsPrimary($beneficiaryId, $bankDetailId)
    {
        /** 1st update all bank details to none primary */
        UserBeneficiaryBankDetail::where('beneficiary_id', '=', $beneficiaryId)->update(['is_primary' => 0]);

        /** 2nd update selected bank details to primary */
        return UserBeneficiaryBankDetail::where('id', '=', $bankDetailId)
                                ->where('beneficiary_id', '=', $beneficiaryId)
                                ->update(['is_primary' => 1]);
    }
    
    public function findByIdAndOTP($request)
    {
        return UserBeneficiaryBankDetail::where('id', '=', $request["bank_detail_id"])
                            ->where('otp', '=', $request["otp"])
                            ->where('beneficiary_id', '=', $request["beneficiary_id"])
                            ->count();
    }        
}
