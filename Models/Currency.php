<?php

namespace App\Models;

use App\Traits\ModelTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class Currency extends Model
{
    use HasFactory, SoftDeletes, ModelTranslations;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'currencies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'code',
        'symbol',
        'status',
        'languages',
        'is_prefix_symbol',
        'is_default',
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
        'title' => 'array',
        'languages' => 'array',
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i'
    ];

    /**
     * The attributes that are have many translations.
     *
     * @var array
     */
    protected $translatable = [
        'title',
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


    
    public static function currentCurrencySymbol()
    {
        $currency_code = config('app.currency');

        $symbol = Cache::remember('currency_symbol_'.$currency_code, 3600, function () use ($currency_code) {
            return static::select('symbol')->where('code', $currency_code)->first()?->symbol;
        });

        return Cache::get('currency_symbol_'.$currency_code);
    }
}
