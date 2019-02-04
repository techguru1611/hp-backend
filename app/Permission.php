<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\User;

class Permission extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'slug', 'created_by', 'updated_by'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    /**
     * Add/Edit permission.
     * 
     * @param: Array of Permission object as key / value pair
     * 
     * @return: Newly/Updated Permission object.
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && !empty($data['id']) && $data['id'] > 0) {
            $permission = Permission::find($data['id']);
            $permission->update($data);
            return Permission::find($data['id']);
        } else {
            return Permission::create($data);
        }
    }

    /**
     * Get permission count with searched filter.
     * 
     * @param String $searchByKeyword
     * 
     * @return Permission collection
     *
     */
    public static function getPermissionCount($searchByKeyword)
    {
        $totalCount = Permission::count();
        if ($searchByKeyword !== null) {
            $totalCount = Permission::where('name', 'LIKE', "%$searchByKeyword%")
                            ->where('slug', 'LIKE', "%$searchByKeyword%")
                            ->count();
        }

        return $totalCount;
    }

    /**
     * Get permission count with searched filter.
     * 
     * @param String $searchByKeyword
     * 
     * @return Permission collection
     *
     */
    public static function getPermissionList($limit, $offset, $sort, $order, $searchByKeyword)
    {
        $permissionQuery = Permission::query();

        if ($searchByKeyword !== null) {
            $totalCount = $permissionQuery->where('name', 'LIKE', "%$searchByKeyword%")
                            ->where('slug', 'LIKE', "%$searchByKeyword%");
        }

        $PermissionList = $permissionQuery->orderBy($sort, $order)
            ->orderBy('id', 'DESC')
            ->take($limit)
            ->offset($offset)
            ->get();
        return $PermissionList;
    }
}
