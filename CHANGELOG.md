# Changelog

All notable changes to `laravel-filepond` will be documented in this file.

## Unreleased

- Laravel 13 support added. ✨

## 12.3.6 - 2026-03-05

- Filepond model null during upload in rare cases fixed. 🐛
- New test cases added to support above changes. 🧪

## 12.3.5 - 2026-01-30

- Fail-safe expired files cleanup added. 🐛

## 12.3.4 - 2025-10-09

- Fails to retrieve bucket name when disk name is not s3 fixed. 🐛

## 12.3.3 - 2025-10-02

- Unsupported disk driver exception for `getFile()` method added. ✅
- New test cases added to support above changes. 🧪

## 12.3.2 - 2025-10-01

- Filepond model in config not acknowledged by driver fixed. 🐛

## 12.3.1 - 2025-09-20

- Added full chunk upload support for S3 storage. ✨

## 12.2.1 - 2025-07-16

- Added PHPDoc for IDE autocomplete support. 🚀

## 12.1.1 - 2025-05-08

- Fixed overriding the disk default visibility #75. 🐛

## 12.1.0 - 2025-03-19

- Mimetype added in fileinfo response. ✨

## 12.0.0 - 2025-02-27

- Laravel 12 support added. ✨
