<?php

namespace App\Models;

use AhmedAliraqi\LaravelMediaUploader\Entities\Concerns\HasUploader;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Company extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, HasUploader;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'companies';

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
        'starter_id',
        'name',
        'email',
        'phone',
        'web',
        'identity',
        'tax_identity'
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
        'updated_at' => 'datetime:d/m/Y H:i'
    ];


    protected $appends = [
        'image',
    ];

    /**
     * Get the starter of the company.
     */
    public function starter()
    {
        return $this->hasOne(User::class, 'id', 'starter_id');
    }

    /**
     * Get the users of the company.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'company_id', 'id');
    }


    public function getImageAttribute()
    {
        $media =  $this->media()->first();
        return [
            "id" => $media?->id,
            "srcset" => $media?->getSrcset(),
            "url" => $media?->getUrl(),
        ];
    }
}
