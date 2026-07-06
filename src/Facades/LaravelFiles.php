<?php

namespace Vhar\LaravelFiles\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Vhar\LaravelFiles\Models\File upload(\Illuminate\Http\UploadedFile $file, ?string $disk = null, string $directory = '')
 * @method static void attach(\Illuminate\Database\Eloquent\Model $model, \Vhar\LaravelFiles\Models\File $file, string $collection = 'default', int $sort = 0)
 * @method static void detach(\Illuminate\Database\Eloquent\Model $model, \Vhar\LaravelFiles\Models\File $file, string $collection = 'default')
 * @method static bool exists(\Vhar\LaravelFiles\Models\File $file)
 * @method static void forceDelete(\Vhar\LaravelFiles\Models\File $file)
 */

class LaravelFiles extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-files';
    }
}
