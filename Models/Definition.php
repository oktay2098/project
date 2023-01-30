<?php

namespace App\Models;

use App\Traits\ModelTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Definition extends Model
{
    use HasFactory, SoftDeletes, ModelTranslations;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'definitions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'type',
        'payload',
        'status',
        'languages'
    ];


    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
        'title' => 'array',
        'payload' => 'array',
        'languages' => 'array'
    ];

    /**
     * The attributes that are have many translations.
     *
     * @var array
     */
    public $translatable = [
        'title',
    ];


    const types = [
        "facility" => "facility",
        "cars feature" => "car_feature",
        "property feature" => "property_feature",
        "property_type" => "property_type",
        "car brand" => "car_brand",
        "car model" => "car_model",
        "car class" => "car_class",
        "cars body style" => "cars_body_style",
        "fuel type" => "fuel",
        "color" => "color",
        "drivetrain" => "drivetrain",
        "properties_category" => "properties_category",
        "transmission" => "transmission"
    ];

    /**
     * Get the model's title by language.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    // protected function title(): Attribute
    // {
    //     return Attribute::get(
    //         function ($value) {
    //             return $value;
    //             $decodedValue = json_decode($value, true);
    //             if ($this->displayLanguage) {
    //                 if (isset($decodedValue[$this->displayLanguage])) {
    //                     return $decodedValue[$this->displayLanguage];
    //                 }
    //             }
    //             if (isset($decodedValue[App::currentLocale()])) {
    //                 return $decodedValue[App::currentLocale()];
    //             }
    //             return '';
    //         }
    //     );
    // }



    public function interior_color_cars()
    {
        return $this->hasMany(Car::class, 'interior_color_id', 'id');
    }

    public function exterior_color_cars()
    {
        return $this->hasMany(Car::class, 'exterior_color_id', 'id');
    }

    public function fuel_cars()
    {
        return $this->hasMany(Car::class, 'fuel_type_id', 'id');
    }

    public function transmission_cars()
    {
        return $this->hasMany(Car::class, 'transmission_id', 'id');
    }

    public function drivetrain_cars()
    {
        return $this->hasMany(Car::class, 'drivetrain_id', 'id');
    }

    public function body_style_cars()
    {
        return $this->hasMany(Car::class, 'body_style_id', 'id');
    }

    public function brand_cars()
    {
        return $this->hasMany(Car::class, 'brand_id', 'id');
    }

    public function features_properties()
    {
        return $this->hasMany(PropertyFeatures::class, 'feature_id', 'id');
    }

    public function classes_car()
    {
        return $this->hasMany(Car::class, 'class_id', 'id');
    }

    public function feature_cars()
    {
        return $this->belongsToMany(Car::class, 'car_features', 'feature_id', 'car_id');
    }
}
