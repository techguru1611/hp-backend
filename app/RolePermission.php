<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Permission;

class RolePermission extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_id', 'permission_id', 'is_allowed', 'created_by', 'updated_by'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Reference to the Permission.
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    /**
     * Reference to the roles.
     */
    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }

    /**
     * Add/Edit permission to tole.
     * 
     * @param: Array with key/value pair. Role / Permission Id and is_allowed key
     * 
     * @return: Newly/Updated Permission/Role object.
     */
    public function insertUpdate($data)
    {
        $rolePermission = RolePermission::where("role_id", '=', $data["role_id"])
                            ->where("permission_id", '=', $data["permission_id"])
                            ->first();

        if($rolePermission){
            $rolePermission->update($data);
            return  RolePermission::where("role_id", '=', $data["role_id"])
                        ->where("permission_id", '=', $data["permission_id"])
                        ->first();
        } else {
            return RolePermission::create($data);
        }
    }

    /**
     * Add/Edit permission to tole.
     * 
     * @param: Array with key/value pair. Role / Permission Id and is_allowed key
     * 
     * @return: Newly/Updated Permission/Role object.
     */
    public function getPermissionByRoleId($role_id)
    {   
        $permission = RolePermission::join('permissions', 'permissions.id',  '=', 'role_permissions.permission_id')
                    ->where("role_id", $role_id)
                    ->select(['permission_id', 'permissions.name', 'permissions.slug', 'is_allowed'])
                    ->get();

        return $permission;
    }
}
