# Changelog

All notable changes to `laravel-filepond` will be documented in this file.

## 9.2.2 - 2026-11-02

> [!IMPORTANT]
> Breaking changes in migration because of metadata plugin support.
> Publish and run the new migration after updating to this version.

- Added `Filepond::field()->preview()` helper method to preview temporary uploads after form refresh. ✨
- Added `@filepondPreview()` blade directive, does the same as above with less boilerplate. ✨
- Restore route now streams file response instead of loading into memory. 🚀
- Restore route enforces `max_previewable_file_size` server-side. 🐛
- Restore route input validation and error handling improved. 🐛
- Experimental `getDataURL()` method removed. 🧹
- Native AWS S3 multipart upload support added for chunk uploads with resume capability. ✨
- Support for [filepond-plugin-file-metadata](https://pqina.nl/filepond/docs/api/plugins/file-metadata/) added. ✨
- Added `Filepond::field()->getMetadata()` helper method. ✨
- Invalid chunk uploads throw `InvalidChunkException` so FilePond can retry the chunk. 🐛
- Filepond model `null` exception in rare cases fixed. 🐛
- Filepond migration proper indexing added. 📋
- Development environment moved to `serversideup/php`. 🐋
- New test cases added to support above changes. 🧪

## 9.1.2 - 2025-07-12

- Mimetype added in fileinfo response. ✨
- Fixed overriding the disk default visibility. 🐛
- Added PHPDoc for IDE autocomplete support. 🚀
- Docker compose development image isolated. 🧑‍💻

## 9.0.2 - 2024-07-30

- Fixed large file processing in third party storage 🐛.
- Docker development environment isolated 🐳.
- Filepond disk test cases added ✅.

## 9.0.1 - 2024-07-10

- Fixed large file processing (out of memory exception) 🐛.

## 9.0.0 - 2023-11-23

- Locked package version to Laravel 9. 🔒
- Moved to major versioning to support each Laravel release. ✨
- Removed unused dependencies to reduce conflict.
