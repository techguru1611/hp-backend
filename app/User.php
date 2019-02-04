<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_id', 'full_name', 'mobile_number', 'email', 'password', 'gender', 'otp', 'otp_date', 'otp_created_date', 'verification_status', 'verification_status_updated_by', 'verification_status_updater_role', 'last_activity_at', 'language', 'user_kyc_status', 'kyc_uploaded_at', 'kyc_status', 'kyc_comment','address', 'street_address', 'locality', 'country','state','city','zip_code','latitude','longitude',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'otp', 'otp_date', 'otp_created_date', 'last_activity_at'
    ];

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function userDetail()
    {
        return $this->hasOne(UserDetail::class, 'user_id');
    }

    public function commissionDetail()
    {
        return $this->hasOne(AgentCommission::class, 'agent_id');
    }

    public function senderTransaction()
    {
        return $this->hasMany(UserTransaction::class, 'from_user_id');
    }

    public function receiverTransaction()
    {
        return $this->hasMany(UserTransaction::class, 'to_user_id');
    }

    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }

    public function addMoneyRequest()
    {
        return $this->hasMany(AgentAddOrWithdrawMoneyRequest::class, 'user_id');
    }

    public function cashInOrOutMoneyRequest()
    {
        return $this->hasMany(CashInOrOutMoneyRequest::class, 'user_id');
    }

    public function transferMoneyRequest()
    {
        return $this->hasMany(TransferMoneyRequest::class, 'from_user_id');
    }
    
    public function notification()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function evoucherRequest()
    {
        return $this->hasMany(EvoucherRequest::class, 'from_user_id');
    }

    public function loginHistory()
    {
        return $this->hasMany(UserLoginHistory::class, 'user_id');
    }

    /**
     * @param $role
     * @return bool
     */
    public function hasRole($role)
    {
        return null !== $this->role()->whereIn('slug', $role)->first();
    }

    public function auditTransaction()
    {
        return $this->hasMany(AuditTransaction::class, 'transaction_user');
    }

    /**
     * Insert and Update User
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && !empty($data['id']) && $data['id'] > 0) {
            $user = User::find($data['id']);
            $user->update($data);
            return User::find($data['id']);
        } else {
            return User::create($data);
        }
    }

    /**
     * Referece to user's KYC Documents
     */
    public function kycDocument()
    {
        return $this->hasMany(KycDocument::class, 'user_id');
    }

    /**
     * Referece to user's KYC Documents
     */
    public function kycDocumentComments()
    {
        return $this->hasMany(KycDocumentComment::class, 'user_id');
    }

    public static function getNearByAgentCount($northEastLat, $northEastLng, $southWestLat, $southWestLng, $searchByAgentName, $searchByAddress, $searchByStreetAddress, $searchByLocality, $searchByCountry, $searchByCity, $searchByState, $searchByZipCode){
        $agentDataCount = User::leftJoin('user_details','user_details.user_id','=','users.id')->where('role_id',Config::get('constant.AGENT_ROLE_ID'));

        // get data in Bound - Start
        if ($southWestLat != null && $northEastLat != null){
            $agentDataCount = $agentDataCount->whereBetween('users.latitude', [$southWestLat, $northEastLat]);
        }

        if ($southWestLng != null && $northEastLng != null){
            $agentDataCount = $agentDataCount->whereBetween('users.longitude', [$southWestLng, $northEastLng]);
        }
        // get data in Bound - End

        // search by Agent Name
        if ($searchByAgentName != null){
            $agentDataCount = $agentDataCount->where('users.full_name','LIKE', "%$searchByAgentName%");
        }

        // search by Address
        if ($searchByAddress != null){
            $agentDataCount = $agentDataCount->where('users.address','LIKE', "%$searchByAddress%");
        }

        // search by Street Address
        if ($searchByStreetAddress != null){
            $agentDataCount = $agentDataCount->where('users.street_address','LIKE', "%$searchByStreetAddress%");
        }
        // search by Locality
        if ($searchByLocality != null){
            $agentDataCount = $agentDataCount->where('users.locality','LIKE', "%$searchByLocality%");
        }
        // search by country
        if ($searchByCountry != null){
            $agentDataCount = $agentDataCount->where('users.country','LIKE', "%$searchByCountry%");
        }
        // search by state
        if ($searchByState != null){
            $agentDataCount = $agentDataCount->where('users.state','LIKE', "%$searchByState%");
        }

        // search by City
        if ($searchByCity != null){
            $agentDataCount = $agentDataCount->where('users.city','LIKE', "%$searchByCity%");
        }

        // search by Zip Code
        if ($searchByZipCode != null){
            $agentDataCount = $agentDataCount->where('users.zip_code', $searchByZipCode);
        }

        return $agentDataCount->count();
    }

    public static function getNearByAgent($limit, $offset, $sort, $order, $latitude, $longitude, $northEastLat, $northEastLng, $southWestLat, $southWestLng,
                                          $searchByAgentName, $searchByAddress, $searchByStreetAddress, $searchByLocality, $searchByCountry, $searchByCity, $searchByState, $searchByZipCode){

        $agentData = User::leftJoin('user_details','user_details.user_id','=','users.id')->where('role_id',Config::get('constant.AGENT_ROLE_ID'));

        // get data in Bound - Start
        if ($southWestLat != null && $northEastLat != null){
            $agentData = $agentData->whereBetween('users.latitude', [$southWestLat, $northEastLat]);
        }

        if ($southWestLng != null && $northEastLng != null){
            $agentData = $agentData->whereBetween('users.longitude', [$southWestLng, $northEastLng]);
        }
        // get data in Bound - End

        // search by Agent Name
        if ($searchByAgentName != null){
            $agentData = $agentData->where('users.full_name', 'LIKE', "%$searchByAgentName%");
        }

        // search by Address
        if ($searchByAddress != null){
            $agentData = $agentData->where('users.address','LIKE', "%$searchByAddress%");
        }

        // search by Street Address
        if ($searchByStreetAddress != null){
            $agentData = $agentData->where('users.street_address','LIKE', "%$searchByStreetAddress%");
        }
        // search by Locality
        if ($searchByLocality != null){
            $agentData = $agentData->where('users.locality','LIKE', "%$searchByLocality%");
        }
        // search by country
        if ($searchByCountry != null){
            $agentData = $agentData->where('users.country','LIKE', "%$searchByCountry%");
        }
        // search by state
        if ($searchByState != null){
            $agentData = $agentData->where('users.state','LIKE', "%$searchByState%");
        }

        // search by City
        if ($searchByCity != null){
            $agentData = $agentData->where('users.city','LIKE', "%$searchByCity%");
        }

        // search by Zip Code
        if ($searchByZipCode != null){
            $agentData = $agentData->where('users.zip_code', $searchByZipCode);
        }

        $agentData = $agentData->orderBy('distance', 'asc')
            ->orderBy('users.'.$sort, $order)
            ->take($limit)
            ->offset($offset)
            ->get([
                'users.full_name',
                'users.mobile_number',
                'users.address',
                'users.street_address',
                'users.locality',
                'user_details.photo',
                'users.country','users.state','users.city','users.zip_code','users.latitude','users.longitude',
                DB::Raw('ROUND((6371 * acos( cos( radians(' . $latitude . ') ) * cos( radians( users.latitude ) ) * cos( radians( users.longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( users.latitude ) ) ) ),2) AS distance'),
            ]);

        return $agentData;
    }
}
