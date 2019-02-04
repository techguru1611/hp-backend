<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Commission extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'commission_management';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['start_range', 'end_range', 'amount_range', 'admin_commission', 'agent_commission', 'government_share', 'transaction_type', 'status', 'agent_id', 'created_by', 'updated_by'];

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

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * To get all commission data
     * @param Integer $userId User Id
     * @return Transaction collection
     */
    public static function getListing($limit, $offset, $sort, $order, $searchByRange, $searchByHelapayShare)
    {
        $commissionData = Commission::whereNull('agent_id');

        // Search by range
        if ($searchByRange !== null) {
            $commissionData = $commissionData->where('amount_range', 'LIKE', "%$searchByRange%");
        }

        // Search by helapay share
        if ($searchByHelapayShare !== null) {
            $commissionData = $commissionData->where('admin_commission', 'LIKE', "%$searchByHelapayShare%");
        }

        $commissionData = $commissionData->orderBy($sort, $order)
            ->orderBy('id', 'ASC')
            ->take($limit)
            ->offset($offset)
            ->get([
                'id',
                'status',
                'start_range',
                'end_range',
                'admin_commission AS _admin_commission',
                DB::raw('CASE WHEN end_range IS NULL THEN CONCAT(`start_range`, "+") ELSE CONCAT(`start_range`, "-", `end_range`) END AS amount_range'),
                // DB::raw('CONCAT(`admin_commission`, "%") AS admin_commission'),
                'admin_commission',
            ]);
        return $commissionData;
    }

}
