<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPackage extends Model
{
    use HasFactory, SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'package_item_id',
        'subscription_reference_code',
        'status',
        'expire_date',
        'price',
        'currency_id',
        'balance'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'expire_date' => 'datetime:Y-m-d H:i',
    ];


    public function plan()
    {
        $res = $this->belongsTo(PackageItem::class, 'package_item_id', 'id');
        return $res;
    }

    public function user()
    {
        $res = $this->belongsTo(User::class, 'user_id', 'id');
        return $res;
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }
}
