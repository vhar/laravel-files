<?php

namespace Vhar\LaravelFiles\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Vhar\LaravelFiles\Models\File;
use Vhar\LaravelFiles\Facades\LaravelFiles;

trait HasFiles
{
    public function files(?string $collection = null): MorphToMany
    {
        $relation = $this->morphToMany(File::class, 'fileable')
            ->withPivot(['collection', 'sort']);

        if ($collection !== null) {
            $relation->wherePivot('collection', $collection);
        }

        return $relation;
    }

    public function file(?string $collection = null): ?File
    {
        return $this->files($collection)->first();
    }

    public function addFile(File $file, string $collection = 'default', int $sort = 0): void
    {
        LaravelFiles::attach($this, $file, $collection, $sort);
    }

    public function removeFile(File $file): void
    {
        LaravelFiles::detach($this, $file);
    }
}