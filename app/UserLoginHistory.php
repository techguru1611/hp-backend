<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Config;

class UserLoginHistory extends Model
{
    //
    protected $table = 'user_login_history';

    protected $fillable = [
        'user_id', 'ip_address', 'device_id', 'platform', 'browser', 'country_code', 'region_name', 'city_name', 'zip_code', 'latitude', 'longitude', 'status',
    ];

    public function getPlatformDetail($user_agent)
    {
        if (preg_match('/linux/i', $user_agent)) {
            $platform = 'LINUX';
        } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
            $platform = 'MAC';
        } elseif (preg_match('/windows|win32/i', $user_agent)) {
            $platform = 'Windows';
        } else {
            $platform = null;
        }

        return $platform;
    }
    public function getBrowserDetail($user_agent)
    {
        if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {
            $bname = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $bname = 'Mozilla Firefox';
        } elseif (preg_match('/Chrome/i', $user_agent)) {
            $bname = 'Google Chrome';
        } elseif (preg_match('/Safari/i', $user_agent)) {
            $bname = 'Apple Safari';
        } elseif (preg_match('/Opera/i', $user_agent)) {
            $bname = 'Opera';
        } elseif (preg_match('/Netscape/i', $user_agent)) {
            $bname = 'Netscape';
        } else {
            $bname = null;
        }

        return $bname;
    }

    /**
     * To get login history
     * @param Integer $searchByUser User Id
     * @param String $searchByBrowser
     * @param String $searchByCreatedAt
     * @param String $searchByLocation
     * @param String $searchByStatus
     * @return UserLoginHistory collection
     *
     */
    public static function getLoginHistoryCount($searchByUser, $searchByFullName, $searchByBrowser, $searchByCreatedAt, $searchByLocation)
    {

        $userLoginHistoryCount = UserLoginHistory::leftJoin('users', 'users.id', '=', 'user_login_history.user_id');
        // Search by user
        if ($searchByUser !== null) {
            $userLoginHistoryCount = $userLoginHistoryCount->where('user_login_history.user_id', $searchByUser);
        }

        // Search by Full Name
        if ($searchByFullName !== null) {
            $userLoginHistoryCount = $userLoginHistoryCount->where('users.full_name', 'LIKE', "%$searchByFullName%");
        }

        // Search by Browser
        if ($searchByBrowser !== null) {
            $userLoginHistoryCount = $userLoginHistoryCount->where(DB::raw("CASE WHEN user_login_history.platform = '" . Config::get('constant.IOS_PLATFORM') . "' THEN '". Config::get('constant.IOS_PLATFORM') ."' WHEN user_login_history.platform = '" . Config::get('constant.ANDROID_PLATFORM') . "' THEN '". Config::get('constant.ANDROID_PLATFORM') ."' ELSE user_login_history.browser END"), 'LIKE', "%$searchByBrowser%");
        }

        // Search by Location
        if ($searchByLocation !== null) {
            $userLoginHistoryCount = $userLoginHistoryCount->where(DB::raw("CASE WHEN user_login_history.region_name IS null OR user_login_history.region_name = '' THEN CASE WHEN user_login_history.country_code IS NOT null AND user_login_history.country_code != '' THEN user_login_history.country_code ELSE '' END WHEN user_login_history.country_code IS null OR user_login_history.country_code = '' THEN CASE WHEN user_login_history.region_name IS NOT null AND user_login_history.region_name != '' THEN user_login_history.region_name ELSE '' END ELSE CONCAT(user_login_history.region_name, ', ', user_login_history.country_code) END"), 'LIKE', "%$searchByLocation%");
        }

        // Search by login created date
        if ($searchByCreatedAt !== null) {
            $userLoginHistoryCount = $userLoginHistoryCount->where(DB::raw('DATE_FORMAT(user_login_history.created_at, "%d-%m-%y,%h:%i %p")'), 'LIKE', "%$searchByCreatedAt%");
        }

        $userLoginHistoryCount = $userLoginHistoryCount->count();
        return $userLoginHistoryCount;
    }

    public static function getLoginHistory($limit, $offset, $sort, $order, $searchByUser, $searchByFullName, $searchByBrowser, $searchByCreatedAt, $searchByLocation)
    {
        $userLoginHistory = UserLoginHistory::leftJoin('users', 'users.id', '=', 'user_login_history.user_id');
        // Search by user
        if ($searchByUser !== null) {
            $userLoginHistory = $userLoginHistory->where('user_login_history.user_id', $searchByUser);
        }

        // Search by Full Name
        if ($searchByFullName !== null) {
            $userLoginHistory = $userLoginHistory->where('users.full_name', 'LIKE', "%$searchByFullName%");
        }

        // Search by Browser
        if ($searchByBrowser !== null) {
            $userLoginHistory = $userLoginHistory->where(DB::raw("CASE WHEN user_login_history.platform = '" . Config::get('constant.IOS_PLATFORM') . "' THEN '". Config::get('constant.IOS_PLATFORM') ."' WHEN user_login_history.platform = '" . Config::get('constant.ANDROID_PLATFORM') . "' THEN '". Config::get('constant.ANDROID_PLATFORM') ."' ELSE user_login_history.browser END"), 'LIKE', "%$searchByBrowser%");
        }

        // Search by Location
        if ($searchByLocation !== null) {
            $userLoginHistory = $userLoginHistory->where(DB::raw("CASE WHEN user_login_history.region_name IS null OR user_login_history.region_name = '' THEN CASE WHEN user_login_history.country_code IS NOT null AND user_login_history.country_code != '' THEN user_login_history.country_code ELSE '' END WHEN user_login_history.country_code IS null OR user_login_history.country_code = '' THEN CASE WHEN user_login_history.region_name IS NOT null AND user_login_history.region_name != '' THEN user_login_history.region_name ELSE '' END ELSE CONCAT(user_login_history.region_name, ', ', user_login_history.country_code) END"), 'LIKE', "%$searchByLocation%");
        }

        // Search by login created date
        if ($searchByCreatedAt !== null) {
            $userLoginHistory = $userLoginHistory->where(DB::raw('DATE_FORMAT(user_login_history.created_at, "%d-%m-%y,%h:%i %p")'), 'LIKE', "%$searchByCreatedAt%");
        }

        $userLoginHistory = $userLoginHistory->orderBy($sort, $order)
            ->orderBy('id', 'DESC')
            ->take($limit)
            ->offset($offset)
            ->get([
                'user_login_history.id',
                'users.full_name',
                DB::raw("CASE WHEN user_login_history.platform = '" . Config::get('constant.IOS_PLATFORM') . "' THEN '". Config::get('constant.IOS_PLATFORM') ."' WHEN user_login_history.platform = '" . Config::get('constant.ANDROID_PLATFORM') . "' THEN '". Config::get('constant.ANDROID_PLATFORM') ."' ELSE user_login_history.browser END AS browser"),
                'user_login_history.created_at',
                DB::raw("CASE WHEN user_login_history.region_name IS null OR user_login_history.region_name = '' THEN
                                CASE WHEN user_login_history.country_code IS NOT null AND user_login_history.country_code != '' THEN user_login_history.country_code
                                    ELSE ''
                                END
                            WHEN user_login_history.country_code IS null OR user_login_history.country_code = '' THEN
                                CASE WHEN user_login_history.region_name IS NOT null AND user_login_history.region_name != '' THEN user_login_history.region_name
                                    ELSE ''
                                END
                            ELSE CONCAT(user_login_history.region_name, ', ', user_login_history.country_code)
                        END AS location"),
                'user_login_history.status',
            ]);

        return $userLoginHistory;
    }
}
