<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewReply extends Model
{
    use HasFactory, SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reviews_replies';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'status',
        'text',
        'review_id',
    ];
    
    protected $hidden = [
        'deleted_at',
        'user_id'
    ];

    public function user()
    {
        return $this->hasOne(\App\Models\User::class,  'id','user_id');
    }

    public function review()
    {
        return $this->belongsTo(\App\Models\Review::class,  'id','review_id');
    }
}
