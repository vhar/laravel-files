# Laravel Files

A lightweight Laravel package for uploading files and attaching them to Eloquent models.

## Features

- Upload files to any Laravel filesystem disk
- Associate files with any Eloquent model
- Organize files into collections
- Automatic orphan file cleanup
- Optional file deduplication using checksum
- Laravel 11 & 12 support
- PHP 8.2+

---

## Installation

Install the package via Composer:

```bash
composer require vhar/laravel-files
```

Run migrations:

```bash
php artisan migrate
```

Optionally publish the configuration:

```bash
php artisan vendor:publish --tag=laravel-files-config
```

If you want to customize the database structure, publish the migration stubs:

```bash
php artisan vendor:publish --tag=laravel-files-migrations
```

---

## Configuration

```php
return [

    /*
     * Default filesystem disk.
     */
    'disk' => env('FILES_DISK', 'public'),

    /*
     * Reuse existing file records when checksum matches.
     */
    'deduplicate' => true,

    /*
     * Automatically delete files that are no longer attached.
     */
    'delete_orphan_files' => true,

    /*
     * Hash algorithm with used for deduplicates
    */
    'hash_algo' => 'sha1',
];
```

---

## Usage

### Upload a file

```php
use Vhar\LaravelFiles\Facades\LaravelFiles;

$file = LaravelFiles::upload($request->file('image'));
```

Or specify directory and disk:

```php
$file = LaravelFiles::upload(
    $request->file('image'),
    'quiz/images',
    'public'
);
```

---

### Attach a file

```php
LaravelFiles::attach($news, $file);
```

Attach to a collection:

```php
LaravelFiles::attach($news, $file, 'gallery');
```

---

### Detach a file

Detach from all collections:

```php
LaravelFiles::detach($news, $file);
```

Detach only from one collection:

```php
LaravelFiles::detach($news, $file, 'gallery');
```

---

### Force delete

Remove the file from every model and delete it from storage.

```php
LaravelFiles::forceDelete($file);
```

---

### Check file exists

```php
LaravelFiles::exists($file);
```

---

## HasFiles trait

Add the trait to any Eloquent model.

```php
use Vhar\LaravelFiles\Traits\HasFiles;

class Quiz extends Model
{
    use HasFiles;
}
```

Access files:

```php
// Get the first file
$news->file();

// Get the first file from a specific collection
$news->file('gallery');

// Get all files
$news->files()->get();

// Get files from a specific collection
$news->files('gallery')->get();
```

Attach via trait:

```php
$news->addFile($file, 'gallery');
```

Remove:

```php
$news->removeFile($file, 'gallery');
```

---

## File model

```php
$file->url();

$file->humanSize();

$file->size;

$file->mime_type;

$file->original_name;
```

---

## License

This package is open-sourced software licensed under the MIT license.