<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'addresses';
    
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_type',
        'model_id',
        'entered_address',
        'geo',
        'latitude',
        'longitude',
        'country_code',
        'language',
        'formatted_address',
        'country',
        'administrative_1',
        'administrative_2',
        'administrative_3',
        'administrative_4',
        'administrative_5',
        'locality',
        'route',
        'street_number',
        'postal_code'
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
        'geo',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
        'updated_at' => 'datetime:d/m/Y H:i',
        'geo' => 'array'
    ];
    
    /**
     * Get the owner of the address.
     */
    public function owner()
    {
        return $this->hasOne($this->model_type, 'id', 'model_id');
    }
}
