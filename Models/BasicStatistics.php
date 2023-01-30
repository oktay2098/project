<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicStatistics extends Model
{
    use HasFactory;

    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'basic_statistics';
   
   /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
   protected $fillable = [
       'user_id',
       'whatsappCount',
       'emailCount',
       'regularCount'
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

   public function users()
   {
       return $this->hasMany(User::class, 'user_id', 'id');
   }    
}
