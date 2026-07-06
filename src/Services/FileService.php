<?php

namespace Vhar\LaravelFiles\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Vhar\LaravelFiles\Models\File;

class FileService
{
    public function exists(File $file): bool
    {
        return Storage::disk($file->disk)->exists($file->path);
    }

    public function upload(UploadedFile $uploadedFile, ?string $disk = null, string $directory = ''): File
    {
        $disk = $disk ?? config('files.disk', 'public');

        $originalName = $uploadedFile->getClientOriginalName();
        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = pathinfo($originalName, PATHINFO_FILENAME);

        $directory = trim($directory, '/');

        $checksum = hash_file(
            config('files.hash_algo', 'sha1'),
            $uploadedFile->getRealPath()
        );

        if (config('files.deduplicate')) {
            if ($existing = File::where('checksum', $checksum)->first()) {
                return $existing;
            }
        }

        $hash = bin2hex(random_bytes(4));
        $finalFilename = $filename . '_' . $hash;

        $path = $directory
            ? $directory . '/' . $finalFilename . '.' . $extension
            : $finalFilename . '.' . $extension;

        $storedPath = $uploadedFile->storeAs(
            $directory,
            basename($path),
            $disk
        );

        return File::create([
            'disk' => $disk,
            'path' => $storedPath,
            'original_name' => $originalName,
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'checksum' => $checksum,
        ]);
    }

    public function attach(Model $model, File $file, string $collection = 'default', int $sort = 0): void
    {
        $exists = $model->files()
            ->where('file_id', $file->id)
            ->wherePivot('collection', $collection)
            ->exists();

        if ($exists) {
            return;
        }

        $model->files()->attach($file->id, [
            'collection' => $collection,
            'sort' => $sort,
        ]);
    }

    public function detach(Model $model, File $file, ?string $collection = null): void
    {
        $detached = $model->files($collection)->detach($file->id);

        if ($detached === 0) {
            return;
        }

        if (!config('files.delete_orphan_files')) {
            return;
        }

        $this->safeCleanupOrphan($file);
    }

    public function forceDelete(File $file): void
    {
        DB::transaction(function () use ($file) {
            DB::table('fileables')
                ->where('file_id', $file->id)
                ->delete();

            $path = $file->path;
            $disk = $file->disk;

            if (!$file->delete()) {
                throw new \RuntimeException('Unable to delete file record.');
            }

            DB::afterCommit(function () use ($disk, $path) {
                Storage::disk($disk)->delete($path);
            });
        });
    }

    private function safeCleanupOrphan(File $file): void
    {
        if ($this->hasRelations($file)) {
            return;
        }

        usleep(50000);

        if ($this->hasRelations($file)) {
            return;
        }

        $this->forceDelete($file);
    }

    private function hasRelations(File $file): bool
    {
        return DB::table('fileables')
            ->where('file_id', $file->id)
            ->exists();
    }
}