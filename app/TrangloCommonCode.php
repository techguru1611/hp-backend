<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class TrangloCommonCode extends Model
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tranglo_common_codes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['code_type', 'code', 'code_description'];
    
    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Returns collection of specified type
     * 
     * @return
     *      Collection array type of TrangloCommonCode
     */
    public function getListByType($typeId) {
        return TrangloCommonCode::where('code_type', '=', $typeId)
                ->select('id', 'code', 'code_description')
                ->get();
    }
}
