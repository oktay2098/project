<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'favorites';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'favorite_id',
        'favorite_type'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at'        => 'datetime:d/m/Y H:i',
        'updated_at'        => 'datetime:d/m/Y H:i'
    ];

    public function favorite()
    {
        return $this->morphTo();
    }

   /*  public function car()
    {
        return $this->morphedByMany(Car::class, 'favorite');
    }
    
    public function property()
    {
        return $this->morphedByMany(Properties::class, 'favorite');
    } */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
