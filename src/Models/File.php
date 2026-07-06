<?php

namespace Vhar\LaravelFiles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'width',
        'height',
        'checksum',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function humanSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        foreach ($units as $index => $unit) {
            if ($bytes < 1024 || $index === array_key_last($units)) {
                return round($bytes, $unit === 'B' ? 0 : 2) . ' ' . $unit;
            }

            $bytes /= 1024;
        }

        return '0 B';
    }
}