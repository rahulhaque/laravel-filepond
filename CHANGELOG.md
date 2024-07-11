# Changelog

All notable changes to `laravel-filepond` will be documented in this file.

Every major releases specific to Laravel framework versions includes all of the changes below.
Please, use the corresponding version for your project or update if already installed.

## 11.0.1 - 2024-07-10

- Fixed large file processing (out of memory exception) 🐛.

## 10.0.1 - 2024-07-10

- Fixed large file processing (out of memory exception) 🐛.

## 9.0.1 - 2024-07-10

- Fixed large file processing (out of memory exception) 🐛.

## 11.0.0 - 2024-03-15

- Laravel 11 support added. ✨
- Locked package version to Laravel 11. 🔒

## 10.0.0 - 2023-11-23

- Locked package version to Laravel 10. 🔒
- Moved to major versioning to support each Laravel release. ✨
- Removed unused dependencies to reduce conflict.

## 9.0.0 - 2023-11-23

- Locked package version to Laravel 9. 🔒
- Moved to major versioning to support each Laravel release. ✨
- Removed unused dependencies to reduce conflict.

## 8.0.0 - 2023-11-23

- Locked package version to Laravel 8. 🔒
- Moved to major versioning to support each Laravel release. ✨
- Removed unused dependencies to reduce conflict.

## 7.0.0 - 2023-11-23

- Locked package version to Laravel 7. 🔒
- Moved to major versioning to support each Laravel release. ✨
- Removed unused dependencies to reduce conflict.

## 1.12.15 - 2023-11-13

- Fixed union type issue for older PHP versions #49. 🐛

## 1.12.14 - 2023-11-09

- Fixed single file upload as array #47. 🐛
- New test cases added for single file upload as array.
- Bumped minimal PHP version to 7.4.

## 1.12.13 - 2023-10-13

- Filepond null validation bug fixed. 🐛
- New test cases added for null validation.

## 1.12.12 - 2023-10-12

- Filepond file revert logic updated. ⬆️

## 1.12.11 - 2023-10-09

- Nested validation bug fixed #46. 🐛
- New test cases added for nested validation.

## 1.12.10 - 2023-10-06

- Added support for extending `Filepond` model to support #45. ✨
- Added support for filpond table rename to support #14. ✨
- Added Larevel Pint for code styling.

## 1.11.10 - 2023-07-12

- Added support for filepond restore endpoint. ✨

## 1.10.10 - 2023-05-29

- Added option to force skip file ownership check to solve #39.
- Added error message for unsupported filepond endpoints.

## 1.9.10 - 2023-02-23

- Added Laravel 10 support. ✨
- Removed deprecated method `validate()`.
- Readme updated to remove deprecated method.
- Test cases updated to support Laravel 10.

## 1.8.10 - 2022-06-01

- Multiple file uploads sometime won't move to location fixed.
- New test cases added for `moveTo()` method.
