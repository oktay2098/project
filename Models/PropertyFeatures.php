<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyFeatures extends Model
{
    use HasFactory;
    protected $table = 'property_features';

    protected $fillable = [
        'property_id',
        'feature_id',
    ];

    public function properties()
    {
        return $this->hasMany(Definition::class, 'property_id', 'id');
    }
}
