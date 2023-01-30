<?php

namespace App\Models;

use App\Services\PricesService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ModelTranslations;
use Spatie\Translatable\HasTranslations;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use AhmedAliraqi\LaravelMediaUploader\Entities\Concerns\HasUploader;

class Car extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, ModelTranslations, InteractsWithMedia, HasUploader;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cars';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'title',
        'description',
        'meta_desc',
        'price',
        'boosted_up',
        'status',
        'year',
        'is_used',
        'model',
        'kilometre',
        'doors_number',
        'seat_number',
        'cylinders',
        'currency_id',
        'class_id',
        'brand_id',
        'body_style_id',
        'drivetrain_id',
        'transmission_id',
        'exterior_color_id',
        'interior_color_id',
        'fuel_type_id',
        'address_id',
        'author_id',
        'horsepower',
        'engine_displacement',
    ];

    protected $with = [
        'cars_body_style:id,title',
        'interior_color:id,title',
        'exterior_color:id,title',
        'fuel:id,title',
        'car_class:id,title',
        'car_brand:id,title',
        'drivetrain:id,title',
        'transmission:id,title',
        'features',
        'author',
        'media',
        'currency:id,code,symbol,title',
        'car_favorites',
        'address'
    ];

    protected $hidden = [
        'deleted_at',
        'currency_id',
        'currency',
        'media',
    ];
    public $translatable = [
        'title',
        'description',
        'meta_desc'
    ];

    protected $appends = [
        'files',
        'currency_code',
        'currency_symbol',
        'favorite_count'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'payload' => 'array',
        'title' => 'array',
        'description' => 'array',
        'meta_desc' => 'array',
        'languages' => 'array'
    ];


    //Currency part
    public function getCurrencyCodeAttribute()
    {
        return config('app.currency');
    }

    public function getCurrencySymbolAttribute()
    {
        return Currency::currentCurrencySymbol();
    }

    /**
     * Get the user's first name.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function price(): Attribute
    {
        return Attribute::get(
            fn ($value) => PricesService::price($value, $this->currency),
        );
    }
    // /**
    //  * Get the model's title by language.
    //  *
    //  * @return \Illuminate\Database\Eloquent\Casts\Attribute
    //  */
    // protected function title(): Attribute
    // {
    //     return Attribute::get(
    //         $this->getAttributeTranslation()
    //     );
    // }

    // /**
    //  * Get the model's meta_desc by language.
    //  *
    //  * @return \Illuminate\Database\Eloquent\Casts\Attribute
    //  */
    // protected function metaDesc(): Attribute
    // {
    //     return Attribute::get(
    //         $this->getAttributeTranslation()
    //     );
    // }

    // /**
    //  * Get the model's description by language.
    //  *
    //  * @return \Illuminate\Database\Eloquent\Casts\Attribute
    //  */
    // protected function description(): Attribute
    // {
    //     return Attribute::get(
    //         $this->getAttributeTranslation()
    //     );
    // }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function cars_body_style()
    {
        return $this->hasOne(Definition::class, 'id', 'body_style_id');
    }

    public function interior_color()
    {
        return $this->hasOne(Definition::class, 'id', 'interior_color_id');
    }

    public function exterior_color()
    {
        return $this->hasOne(Definition::class, 'id', 'exterior_color_id');
    }

    public function fuel()
    {
        return $this->hasOne(Definition::class, 'id', 'fuel_type_id');
    }
    public function car_model()
    {
        return $this->hasOne(Definition::class, 'id', 'model');
    }
    public function car_class()
    {
        return $this->hasOne(Definition::class, 'id', 'class_id');
    }

    public function car_brand()
    {
        return $this->hasOne(Definition::class, 'id', 'brand_id');
    }

    public function drivetrain()
    {
        return $this->hasOne(Definition::class, 'id', 'drivetrain_id');
    }

    public function transmission()
    {
        return $this->hasOne(Definition::class, 'id', 'transmission_id');
    }

    public function author()
    {
        return $this->hasOne(User::class, 'id', 'author_id');
    }

    public function features()
    {
        return $this->belongsToMany(Definition::class, 'car_features', 'car_id', 'feature_id');
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'model');
    }

    public function getFilesAttribute()
    {
        $modelMedia = [];

        foreach ($this->media as $key => $mediaItem) {
            $modelMedia[$key]['id'] = $mediaItem->id;
            $modelMedia[$key]['file_name'] = $mediaItem->file_name;
            $modelMedia[$key]['mime_type'] = $mediaItem->mime_type;
            $modelMedia[$key]['size'] = $mediaItem->size;
            $modelMedia[$key]['url'] = $mediaItem->getUrl();
            $modelMedia[$key]['srcset'] = $mediaItem->getSrcset();
        }

        return $modelMedia;
    }

    public function car_favorites()
    {
        return $this->morphMany('App\Models\Favorite', 'favorite');
    }

    public function getFavoriteCountAttribute()
    {
        return $this->car_favorites->count();
    }

    public function click_statistic()
    {
        return $this->morphMany('App\Models\ClickStatistics', 'model');
    }
}
