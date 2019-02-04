<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

class AuditTransaction extends Model
{
    use Notifiable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'audit_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'transaction_type_id',
        'transaction_date',
        'transaction_user',
        'action_model_id',
        'action_detail',
        'url',
        'ip_address',
        'user_agent'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'transaction_user');
    }

    public function getUserAgentAttribute()
    {
        return Request::header('User-Agent');
    }

    public function getIpAddressAttribute()
    {
        return Request::ip();
    }

    public function getUrlAttribute()
    {
        if (App::runningInConsole()) {
            return 'console';
        } else {
            return Request::fullUrlWithQuery([]);
        }
    }

    public function setActionDetailAttribute($action_detail)
    {
        $this->attributes['action_detail'] = isset($action_detail) ? json_encode($action_detail) : "";
    }

    public function insertUpdate($auditTransactionObj)
    {
        $auditTransactionObj['user_agent'] = Request::header('User-Agent');
        $auditTransactionObj['ip_address'] = Request::ip();

        if (App::runningInConsole()) {
            $auditTransactionObj['url'] = 'console';
        } else {
            $auditTransactionObj['url'] = Request::fullUrlWithQuery([]);
        }

        if (isset($auditTransactionObj['id']) && !empty($auditTransactionObj['id']) && $auditTransactionObj['id'] > 0) {
            $auditTransaction = AuditTransaction::find($auditTransactionObj['id']);
            $auditTransaction->update($auditTransactionObj);
            return AuditTransaction::find($auditTransactionObj['id']);
        } else {
            return AuditTransaction::create($auditTransactionObj);
        }
    }
}