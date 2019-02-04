<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class KycDocumentComment extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'kyc_document_comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'notes_by', 'notes'];

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

    /**
     * Reference to the user who posted comments..
     */
    public function notesBy()
    {
        return $this->hasOne(User::class, 'id', 'notes_by');
    }

    /**
     * Reference to the user who posted comments..
     */
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}