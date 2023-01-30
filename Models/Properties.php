<?php

namespace App\Models;
use App\Services\PricesService;
use App\Traits\ModelTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use AhmedAliraqi\LaravelMediaUploader\Entities\Concerns\HasUploader;
use Spatie\MediaLibrary\InteractsWithMedia;

class Properties extends Model implements HasMedia
{
    use HasFactory,SoftDeletes, ModelTranslations, HasUploader, InteractsWithMedia;
    protected $table = 'properties';

    protected $fillable = [
        'title',
        'meta_desc',
        'description',
        'bedrooms_number',
        'living_room_number',
        'bathrooms_number',
        'price',
        'currency_id',
        'square',
        'author_id',
        'type_id',
        'floor_number',
        'category_id',
        'status',
        'boosted_up',
        'languages',
    ];

     /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
        'media',
    ];

    /**
     * The attributes that are have many translations.
     *
     * @var array
     */
    public $translatable = [
        'title',
        'meta_desc',
        'description',
    ];

    protected $with = [
        'currency',
        'category',
        'type',
        'address',
        'facilities',
        'property_favorites',
        'features',
        'media',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'languages' => 'array',
        'title' => 'array',
        'meta_desc' => 'array',
        'description' => 'array',
    ];

    protected $appends = [
        'currency_code',
        'currency_symbol',
        'files',
        'favorite_count'
    ];    

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

    public function getCurrencyCodeAttribute()
    {
        return config('app.currency');
    }

    public function getCurrencySymbolAttribute()
    {
        return Currency::currentCurrencySymbol();
    }

	protected function price(): Attribute
    {
        return Attribute::get(
            fn ($value) => PricesService::price($value, $this->currency),
        );
    }
    public function category()
    {
        return $this->hasOne(Definition::class, 'id', 'category_id')->where('type', Definition::types['properties_category'])->select('definitions.id', 'definitions.title');;
    }

    public function type()
    {
        return $this->hasOne(Definition::class, 'id', 'type_id');
    }

    public function address()
    {
        return $this->morphOne(Address::class, 'model');
    }

    public function facilities()
    {
        return $this->belongsToMany(Definition::class, 'property_facilities', 'property_id', 'facility_id')->withPivot('distance')
        ->where('type', Definition::types['facility'])->select('definitions.id', 'definitions.title');
    }

    public function features()
    {
        return $this->belongsToMany(Definition::class, 'property_features', 'property_id', 'feature_id')
        ->where('type', Definition::types['property feature'])->select('definitions.id', 'definitions.title');
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

    public function author()
    {
        return $this->hasOne(User::class, 'id', 'author_id');
    }

    public function property_favorites()
    {
        return $this->morphMany('App\Models\Favorite', 'favorite');
    }  

    public function getFavoriteCountAttribute()
    {
        return $this->property_favorites->count();
    }

    public function click_statistic()
    {
        return $this->morphMany('App\Models\Favorite', 'model');
    }
}
