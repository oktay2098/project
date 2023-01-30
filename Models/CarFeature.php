<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarFeature extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'car_features';

    protected $dates = ['deleted_at'];

    public $guarded = [];

    protected $fillable = [
        'car_id',
        'feature_id',
    ];

    
}