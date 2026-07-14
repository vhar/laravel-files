# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog.

## [1.0.0] - 2026-07-06

### Added

- Initial release
- Upload files
- File deduplication using checksum
- Attach files to any Eloquent model
- Collections support
- Automatic orphan cleanup
- Force delete
- HasFiles trait
- Laravel facade
- Config publishing
- Migration publishing

## [1.0.1] - 2026-07-14

### Added

- Added `FileService::replace()` method for replacing file content while preserving the existing file record ID and relations.
- Added file metadata update during replacement:
  - original name
  - MIME type
  - file size
  - checksum.
- Added facade documentation for the new `replace()` method.

### Changed

- Updated file replacement workflow:
  - replacement no longer creates a new `File` record;
  - existing `fileables` relations remain unchanged.
- Added PHPDoc documentation for `FileService` methods.
