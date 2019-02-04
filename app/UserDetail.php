<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use Notifiable;
    
    protected $table = 'user_details';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'balance_amount', 'commission_wallet_balance', 'country_code', 'photo', 'nationality', 'dob'
    ];

    public function userDetail() {
        return $this->belongsTo(User::class, 'user_id');
    }    
   
    public function receivertransaction() {
        return $this->hasMany(UserTransaction::class, 'to_user_id', 'user_id');
    }

}
