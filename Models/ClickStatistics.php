<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClickStatistics extends Model
{
    use HasFactory;
    
    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'click_statistics';
   
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'usedFor',
        'model_id',
        'model_type'
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


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
