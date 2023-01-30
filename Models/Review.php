<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reviews';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'status',
        'text',
        'stars_value',
        'model_id',
        'model_type',
    ];
    
    protected $hidden = [
        'deleted_at',
        'user_id'
    ];
   
    public function replies()
    {
        return $this->belongsTo(\App\Models\ReviewReply::class,  'id','review_id')->where('status', 1);
    }

    public function user()
    {
        return $this->hasMany(\App\Models\User::class,'id', 'user_id');
    }

}
