# Changelog

All notable changes to `laravel-filepond` will be documented in this file.

## 11.3.5 - 2026-03-05

- Fail-safe expired files cleanup added. 🐛
- Filepond model null during upload in rare cases fixed. 🐛
- New test cases added to support above changes. 🧪

## 11.3.4 - 2025-10-14

- Added full chunk upload support for S3 storage. ✨
- Unsupported disk driver exception for `getFile()` method added. ✅
- New test cases updated to support above changes. 🧪

## 11.2.4 - 2025-07-16

- Added PHPDoc for IDE autocomplete support. 🚀

## 11.1.4 - 2025-05-08

- Fixed overriding the disk default visibility #75. 🐛

## 11.1.3 - 2025-03-23

- Mimetype added in fileinfo response. ✨

## 11.0.3 - 2025-02-26

- Introducing empty or corrupted chunk exception. 🥅
- Docker compose development image isolated. 🧑‍💻

## 11.0.2 - 2024-07-30

- Fixed large file processing in third party storage 🐛.
- Docker development environment isolated 🐳.
- Filepond disk test cases added ✅.

## 11.0.1 - 2024-07-10

- Fixed large file processing (out of memory exception) 🐛.

## 11.0.0 - 2024-03-15

- Laravel 11 support added. ✨
- Locked package version to Laravel 11. 🔒
