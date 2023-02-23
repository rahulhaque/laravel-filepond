# Changelog

All notable changes to `laravel-filepond` will be documented in this file.

## 1.9.10 - 2023-02-23

- Added Laravel 10 support. ✨
- Removed depricated method `validate()`.
- Readme updated to remove depricated method.
- Test cases updated to support Laravel 10.

## 1.8.10 - 2022-06-01

- Multiple file uploads sometime won't move to location fixed.
- New test cases added for `moveTo()` method.

## 1.7.10 - 2022-05-31

- `validate()` method deprication notice added.
- Test cases updated with `Rule::filepond()`.

## 1.7.9 - 2022-05-07

- `Rule::filepond($rules)` documentation added.
- Code cleanup and improvement.

## 1.7.8 - 2022-04-30

- Added custom `Rule::filepond()` to validate uploaded files. ✨
- Readme updated with new validation example.

## 1.6.8 - 2022-02-22

- Added option to `getDataURL()` from file. Know more about the format [here](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs).

## 1.5.8 - 2022-02-15

- Added Laravel 9 support.

## 1.4.8 - 2021-12-31

- Added support for chunk upload with resume capability. ✨
- Added option to change visibility when using third party storage.
- Updated quickstart example in readme.
- Updated `./filepond/config.php` to change url from one place. 

## 1.3.8 - 2021-12-25

- Added option to override permanent storage disk during copying/moving file.

## 1.3.7 - 2021-12-24

- Added support for third party storage. ✨
- File submit response now contains file location and URL for better management.
- Code base restructure and performance improvement.

## 1.2.7 - 2021-10-31

- Fixed delete method call error for multiple upload.

## 1.2.6 - 2021-09-18

- Fixed install error in fresh laravel setup with no database connection config.

## 1.2.5 - 2021-08-05

- Added null value validation support for multiple file uploads using filepond.
- Readme updated with validation example.

## 1.2.4 - 2021-07-24

- Code refactor and performance improvement.
- Package footprint reduction.

## 1.2.3 - 2021-07-22

- Added null value validation support for filepond fields.

## 1.2.2 - 2021-07-21

- Added controller level validation to validate files before moving from temporary storage. ✨
- Addressed workaround where FilePond won't work when laravel debugbar is installed.
- Moved to service implementation for cleaner controller code.
- Fixed an issue where `vendor:publish` will create duplicate migrations.
- Added test cases for filepond revert route.
- Added test cases for `filepond:clear` command.
- Updated documentation for related changes.

## 1.1.2 - 2021-07-16

- Added option for server side validation in `./config/filepond.php` for temporary files.
- Updated documentation.
- Performance improvements.

## 1.0.2 - 2021-05-22

- Added support to show dynamic field name with validation message.
- Added test cases for filepond process route.
- Reduced package bundle size.

## 1.0.1 - 2021-05-21

- Multiple file uploads will be returned as array of objects as per Laravel standard.
- Fixed an issue where temporary storage will not be cleared upon running the command.
- Added test cases for `Filepond` model.

## 1.0.0 - 2021-05-20

- Supports FilePond process and revert routes.
- Added artisan command to clean up temporary storage.
- Added trait support for tracking user uploads.
