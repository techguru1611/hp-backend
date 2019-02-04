<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Blog extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'blogs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'image', 'description'];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Insert and Update Blog
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && !empty($data['id']) && $data['id'] > 0) {
            $blog = Blog::find($data['id']);
            $blog->update($data);
            return Blog::find($data['id']);
        } else {
            return Blog::create($data);
        }
    }
}
