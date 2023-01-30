<?php

namespace App\Http\Controllers;

use AhmedAliraqi\LaravelMediaUploader\Entities\TemporaryFile;
use AhmedAliraqi\LaravelMediaUploader\Http\Controllers\MediaController;
use AhmedAliraqi\LaravelMediaUploader\Http\Requests\MediaRequest;
use AhmedAliraqi\LaravelMediaUploader\Support\Uploader;
use AhmedAliraqi\LaravelMediaUploader\Transformers\MediaResource;
use App\Models\Definition;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Str;

class MediaManagerController extends MediaController
{


    
    public function store(MediaRequest $request)
    {
        $temporaryFile = TemporaryFile::create([
            'token' => Str::random(60),
            'collection' => $request->input('collection', 'default'),
        ]);

        if (is_string($request->file) && base64_decode(base64_encode($request->file)) === $request->file) {
            $temporaryFile->addMediaFromBase64($request->file)
                ->usingFileName(time().'.png')
                ->withResponsiveImages()
                ->toMediaCollection($temporaryFile->collection);
        }

        if ($request->hasFile('file')) {
            $temporaryFile->addMedia($request->file)
                ->usingFileName(Uploader::formatName($request->file))
                ->withResponsiveImages()
                ->toMediaCollection($temporaryFile->collection);
        }

        foreach ($request->file('files', []) as $file) {
            $temporaryFile->addMedia($file)
                ->usingFileName(Uploader::formatName($file))
                ->withResponsiveImages()
                ->toMediaCollection($temporaryFile->collection);
        }

        return MediaResource::collection(
            $temporaryFile->getMedia(
                $temporaryFile->collection ?: 'default'
            )
        )->additional([
            'token' => $temporaryFile->token,
        ]);
    }


    public function deleteAllTheModelMedia($model)
    {
        if ($model instanceof HasMedia) {
            $model->media->each->delete();
        }
    }
}
