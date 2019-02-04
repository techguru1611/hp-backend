<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Testimonial extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'testimonials';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'photo', 'position', 'description'];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Insert and Update Testimonial
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && !empty($data['id']) && $data['id'] > 0) {
            $testimonial = Testimonial::find($data['id']);
            $testimonial->update($data);
            return Testimonial::find($data['id']);
        } else {
            return Testimonial::create($data);
        }
    }

}
