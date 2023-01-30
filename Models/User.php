<?php

namespace App\Models;

use AhmedAliraqi\LaravelMediaUploader\Entities\Concerns\HasUploader;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens, HasRoles, HasUploader, InteractsWithMedia;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

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
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'company_id',
        'language',
        'status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'media',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'loggedin_at'       => 'datetime:d/m/Y H:i',
        'email_verified_at' => 'datetime:d/m/Y H:i',
        'created_at'        => 'datetime:d/m/Y H:i',
        'updated_at'        => 'datetime:d/m/Y H:i'
    ];

    protected $appends = [
        'image',
        'role',
        'user_type',
        'verified',
        'favorite_cars_count',
        'favorite_properties_count',
        'click_email_count',
        'click_regular_count',
        'click_whatsapp_count',
        'subscription_status',
    ];

    public static $withoutAppends = false;

    protected $with = [
        'media',
        'address',
    ];

    public function scopeWithoutAppends($query)
    {
        self::$withoutAppends = true;

        return $query;
    }

    protected function getArrayableAppends()
    {
        if (self::$withoutAppends) {
            return [];
        }

        return parent::getArrayableAppends();
    }

    /**
     * Get the company the user belongs to.
     */
    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    /**
     * Get the starter of the company.
     */
    public function companyStarted()
    {
        return $this->hasOne(Company::class, 'starter_id', 'id');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function getRoleAttribute()
    {
        return $this->getRoleNames();
    }

    public function getVerifiedAttribute()
    {
        return $this->hasVerifiedEmail();
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'model');
    }

    public function favorite_cars()
    {
        return $this->hasMany(Favorite::class)->where('favorite_type', '=', 'App\Models\Car')->with('favorite');
    }

    public function favorite_properties()
    {
        return $this->hasMany(Favorite::class)->where('favorite_type', '=', 'App\Models\Properties')->with('favorite');
    }

    public function isLocal()
    {
        return strtolower($this->address?->country_code ?? '') == 'tr' || strtolower($this->address?->country_code ?? '') == 'tur';
    }

    public function getFavoriteCarsCountAttribute()
    {
        return $this->favorite_cars()->count();
    }

    public function getFavoritePropertiesCountAttribute()
    {
        return $this->favorite_properties()->count();
    }

    public function getUserTypeAttribute()
    {
        return $this->userType();
    }

    public function packages()
    {
        return $this->hasMany(UserPackage::class, 'user_id', 'id');
    }

    public function properties()
    {
        return $this->hasMany(Properties::class, 'author_id', 'id');
    }

    public function cars()
    {
        return $this->hasMany(Car::class, 'author_id', 'id');
    }

    //First way
    public function getClickEmailCountAttribute()
    {
        return $this->hasMany(ClickStatistics::class)->where('usedFor', '=', 'email')->where('user_id', '=', $this->id)->count();
    }

    public function getClickRegularCountAttribute()
    {
        return $this->hasMany(ClickStatistics::class)->where('usedFor', '=', 'regular')->where('user_id', '=', $this->id)->count();
    }

    public function getClickWhatsappCountAttribute()
    {
        return $this->hasMany(ClickStatistics::class)->where('usedFor', '=', 'whatsapp')->where('user_id', '=', $this->id)->count();
    }

    public function getImageAttribute()
    {
        $media =  $this->media?->first();
        return [
            "id" => $media?->id,
            "srcset" => $media?->getSrcset(),
            "url" => $media?->getUrl(),
        ];
    }

    public function getSubscriptionStatusAttribute()
    {
        $subscriptions = [
            'cars' => [
                'status' => 0,
                'balance' => 0,
                'boost_up' => 0,
            ],
            'properties' => [
                'status' => 0,
                'balance' => 0,
                'boost_up' => 0,
            ],
            'status' => 0,
        ];
        if ($this->hasRole(['cars_agent', 'cars_corporate', 'properties_agent', 'properties_corporate'])) {

            $userPackages = DB::table('users_packages')->select(
                'users_packages.id',
                'packages.ads_type',
                'packages.type as package_type',
                'users_packages.balance',
            )
                ->join('package_items', 'users_packages.package_item_id', '=', 'package_items.id')
                ->join('packages', 'package_items.package_id', '=', 'packages.id')
                ->where('users_packages.user_id', $this->id)
                ->where('users_packages.expire_date', '>=', now())
                ->where('users_packages.status', '1')
                ->get();

            if ($userPackages) {
                foreach ($userPackages as $package) {
                    if ($package->package_type == 'regular') {
                        $subscriptions['status'] = 1;
                        if ($package->ads_type == 'cars') {
                            $subscriptions['cars']['status'] = 1;
                            $subscriptions['cars']['balance'] += $package->balance;
                        } elseif ($package->ads_type == 'properties') {
                            $subscriptions['properties']['status'] = 1;
                            $subscriptions['properties']['balance'] += $package->balance;
                        }
                    } elseif ($package->package_type == 'boost_up') {
                        if ($package->ads_type == 'cars') {
                            $subscriptions['cars']['boost_up'] += $package->balance;
                        } elseif ($package->ads_type == 'properties') {
                            $subscriptions['properties']['boost_up'] += $package->balance;
                        }
                    }
                }
            }
        } elseif ($this->hasRole(['admin'])) {
            $subscriptions['status'] = 1;

            $subscriptions['cars']['status'] = 1;
            $subscriptions['cars']['balance'] = 1000;
            $subscriptions['cars']['boost_up'] = 1000;
            $subscriptions['properties']['status'] = 1;

            $subscriptions['properties']['balance'] = 1000;
            $subscriptions['properties']['boost_up'] = 1000;
        }

        return $subscriptions;
    }

    public function userType()
    {
        if ($this->hasRole(['cars_agent', 'properties_agent'])) {
            return 'agent';
        } elseif ($this->hasRole(['cars_corporate', 'properties_corporate'])) {
            return 'corporate';
        } elseif ($this->hasRole(['admin'])) {
            return 'admin';
        } else {
            return 'customer';
        }
    }

    //Second way
    public function basic_statistics()
    {
        return $this->hasOne(BasicStatistics::class, 'user_id', 'id');
    }

    public function hasBasicStatistics()
    {
        $statistics = $this->basic_statistics();
        if ($statistics->count() > 0) {
            return true;
        }

        return false;
    }

    public function decreasePackageBalance($subscriptionType, $ads_type, $amount = 1)
    {
        $userPackage = DB::table('users_packages')->select(
            'users_packages.id',
            'packages.ads_type',
            'packages.type as package_type',
            'users_packages.balance',
        )
            ->join('package_items', 'users_packages.package_item_id', '=', 'package_items.id')
            ->join('packages', 'package_items.package_id', '=', 'packages.id')
            ->where('users_packages.user_id', $this->id)
            ->where('users_packages.expire_date', '>=', now())
            ->where('users_packages.status', '1')
            ->where('users_packages.balance', '>=', (int) $amount)
            ->where('packages.type', $subscriptionType)
            ->where('packages.ads_type', $ads_type)
            ->first();

        if ($userPackage) {
            return UserPackage::where('id', $userPackage->id)->decrement('balance', $amount);
        }

        return false;
    }
}
