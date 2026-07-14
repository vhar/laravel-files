<?php

namespace Vhar\LaravelFiles\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Vhar\LaravelFiles\Models\File;

/**
 * Provides file management operations.
 *
 * Handles file uploading, replacing, attaching, detaching
 * and cleanup of orphaned files.
 */
class FileService
{
    /**
     * Check whether physical file exists on storage.
     *
     * @param File $file File record.
     *
     * @return bool
     */
    public function exists(
        File $file,
    ): bool {
        return Storage::disk($file->disk)
            ->exists($file->path);
    }

    /**
     * Upload new file and create file record.
     *
     * If deduplication is enabled, existing file with the same
     * checksum will be returned.
     *
     * @param UploadedFile $uploadedFile Uploaded file.
     * @param string|null $disk Storage disk.
     * @param string $directory Storage directory.
     *
     * @return File Created or existing file record.
     */
    public function upload(
        UploadedFile $uploadedFile,
        ?string $disk = null,
        string $directory = '',
    ): File {
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

    /**
     * Replace existing file content while keeping file identity.
     *
     * Updates physical file and metadata without creating a new File model.
     *
     * Existing relations with fileable models remain unchanged.
     *
     * @param File $file Existing file record.
     * @param UploadedFile $uploadedFile New uploaded file.
     *
     * @return File Updated file record.
     */
    public function replace(
        File $file,
        UploadedFile $uploadedFile,
    ): File {
        $disk = $file->disk;

        Storage::disk($disk)
            ->delete($file->path);

        $newPath = $uploadedFile->store(
            dirname($file->path),
            $disk
        );

        $file->update([
            'path' => $newPath,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime_type' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'checksum' => hash_file(
                config('files.hash_algo', 'sha1'),
                $uploadedFile->getRealPath()
            ),
        ]);

        return $file;
    }

    /**
     * Attach file to model.
     *
     * Creates polymorphic relation between model and file.
     *
     * @param Model $model Target model.
     * @param File $file File record.
     * @param string $collection File collection name.
     * @param int $sort Sorting order.
     *
     * @return void
     */
    public function attach(
        Model $model,
        File $file,
        string $collection = 'default',
        int $sort = 0,
    ): void {
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

    /**
     * Detach file from model.
     *
     * Optionally removes orphaned files according to configuration.
     *
     * @param Model $model Target model.
     * @param File $file File record.
     * @param string|null $collection File collection name.
     *
     * @return void
     */
    public function detach(
        Model $model,
        File $file,
        ?string $collection = null,
    ): void {
        $detached = $model->files($collection)
            ->detach($file->id);

        if ($detached === 0) {
            return;
        }

        if (!config('files.delete_orphan_files')) {
            return;
        }

        $this->safeCleanupOrphan($file);
    }

    /**
     * Permanently delete file record and physical file.
     *
     * Removes all file relations before deleting the record.
     *
     * @param File $file File record.
     *
     * @return void
     */
    public function forceDelete(
        File $file,
    ): void {
        DB::transaction(function () use ($file) {
            DB::table('fileables')
                ->where('file_id', $file->id)
                ->delete();

            $path = $file->path;
            $disk = $file->disk;

            if (!$file->delete()) {
                throw new \RuntimeException(
                    'Unable to delete file record.'
                );
            }

            DB::afterCommit(function () use ($disk, $path) {
                Storage::disk($disk)->delete($path);
            });
        });
    }

    /**
     * Safely remove orphaned file.
     *
     * Performs delayed check before deleting file to avoid
     * deleting files that are attached again during operation.
     *
     * @param File $file File record.
     *
     * @return void
     */
    private function safeCleanupOrphan(
        File $file,
    ): void {
        if ($this->hasRelations($file)) {
            return;
        }

        usleep(50000);

        if ($this->hasRelations($file)) {
            return;
        }

        $this->forceDelete($file);
    }

    /**
     * Check whether file has attached models.
     *
     * @param File $file File record.
     *
     * @return bool
     */
    private function hasRelations(
        File $file,
    ): bool {
        return DB::table('fileables')
            ->where('file_id', $file->id)
            ->exists();
    }
}