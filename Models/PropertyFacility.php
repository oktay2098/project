<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \App\Models\Facilities;

class PropertyFacility extends Model
{
    use HasFactory;

    protected $table = 'property_facilities';

    protected $fillable = [
        'facility_id',
        'distance',
        'property_id',
    ];
}
