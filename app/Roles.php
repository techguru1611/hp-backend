<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Roles extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug'];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function getRoleBySlug($slug)
    {
        return Roles::where('slug', $slug)->first();
    }

    public function user()
    {
        return $this->hasMany(User::class, 'role_id');
    }

//    public function getRolesWithPermission($param)
    //    {
    //        $getRole = Roles::with('')->get();
    //    }

    /**
     * Add/Edit Role.
     * 
     * @param: Array of Role object as key / value pair
     * 
     * @return: Newly/Updated Role object.
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && !empty($data['id']) && $data['id'] > 0) {
            $role = Roles::find($data['id']);
            $role->update($data);
            return Roles::find($data['id']);
        } else {
            return Roles::create($data);
        }
    }
    
    /**
     * Get role count with searched filter.
     * 
     * @param String $searchByKeyword
     * 
     * @return role collection
     *
     */
    public static function getRoleCount($searchByKeyword)
    {
        $totalCount = Roles::count();
        if ($searchByKeyword !== null) {
            $totalCount = Roles::where('name', 'LIKE', "%$searchByKeyword%")
                            ->where('slug', 'LIKE', "%$searchByKeyword%")
                            ->count();
        }

        return $totalCount;
    }

    /**
     * Get roles with searched filter.
     * 
     * @param String $searchByKeyword
     * 
     * @return role collection
     *
     */
    public static function getRoleList($limit, $offset, $sort, $order, $searchByKeyword)
    {
        $roleQuery = Roles::query();

        if ($searchByKeyword !== null) {
            $totalCount = $roleQuery->where('name', 'LIKE', "%$searchByKeyword%")
                            ->where('slug', 'LIKE', "%$searchByKeyword%");
        }

        $roleList = $roleQuery->orderBy($sort, $order)
            ->orderBy('id', 'DESC')
            ->take($limit)
            ->offset($offset)
            ->get();
        return $roleList;
    }
}
