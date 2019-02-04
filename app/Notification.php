<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Notification extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['message', 'user_id'];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * To get notification count of given user
     * @param Integer $userId User Id
     * @return Notification collection
     */
    public static function userNotificationCount ($userId) {
        return Notification::where('user_id', $userId)->count();
    }

    /**
     * To get notification data of given user
     * @param Integer $userId User Id
     * @param Integer $limit [No. of records you want to display per page]
     * @param Integer $offset [Start offset]
     * @param String $sort Column Name
     * @param String $order ASC / DESC
     * @return Notification collection
     */
    public static function userNotification ($userId, $limit, $offset, $sort, $order) {
        return Notification::where('user_id', $userId)
            ->orderBy($sort, $order)
            ->orderBy('id', 'DESC')
            ->take($limit)
            ->offset($offset)
            ->get();
    }
}
