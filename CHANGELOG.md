# Changelog

All notable changes to `laravel-filepond` will be documented in this file.

## 10.4.3 - 2026-04-08

- Support for [filepond-plugin-file-metadata](https://pqina.nl/filepond/docs/api/plugins/file-metadata/) added. ✨
- New method `Filepond::field()->getMetadata()` added. ✨
- Deprecated method from validation removed. 🧽
- Development environment moved to `serversideup/php`. 🐋
- New test cases added to support above changes. 🧪
- Filepond model `null` exception in rare cases fixed. 🐛
- Filepond migration proper indexing added. 📋
- Typo in database migration fixed. ✍️
- Experimental `getDataURL()` method removed. 🧹
- Unnecessary `pint.json` style removed. 🎨

## 10.3.3 - 2026-03-05

- Fail-safe expired files cleanup added. 🐛
- Filepond model null during upload in rare cases fixed. 🐛
- New test cases added to support above changes. 🧪

## 10.3.2 - 2025-10-25

- Added full chunk upload support for S3 storage. ✨
- Unsupported disk driver exception for `getFile()` method added. ✅
- New test cases added to support above changes. 🧪

## 10.2.2 - 2025-07-16

- Added PHPDoc for IDE autocomplete support. 🚀

## 10.1.2 - 2025-05-08

- Mimetype added in fileinfo response. ✨
- Fixed overriding the disk default visibility. 🐛

## 10.0.2 - 2024-07-30

- Fixed large file processing in third party storage 🐛.
- Docker development environment isolated 🐳.
- Filepond disk test cases added ✅.

## 10.0.1 - 2024-07-10

- Fixed large file processing (out of memory exception) 🐛.

## 10.0.0 - 2023-11-23

- Locked package version to Laravel 10. 🔒
- Moved to major versioning to support each Laravel release. ✨
- Removed unused dependencies to reduce conflict.
