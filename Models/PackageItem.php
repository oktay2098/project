<?php

namespace App\Models;

use App\Services\PricesService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageItem extends Model
{
    use HasFactory, SoftDeletes;
   
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'package_id',
        'subscription_plan_code',
        'price',
        'currency_id',
        'ads_number',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
        'package_id',
        'currency_id',
        'package',
        'currency',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'languages' => 'array'
    ];

    protected $with = [
        'currency',
    ];


    protected $appends = [
        'currency_code',
        'total',
    ];

    public function getCurrencyCodeAttribute()
    {
        return $this->currency?->code;
    }

    public function getTotalAttribute()
    {
        return $this->price * $this->package->period;
    }

    
    // protected function price(): Attribute
    // {
    //     return Attribute::get(
    //         fn ($value) => PricesService::price($value, $this->currency),
    //     );
    // }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'id')->without('items');
    }

    public function getType()
    {
        return $this->package?->type;
    }

    public function getPeriod()
    {
        return $this->package?->period;
    }


    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }
}
