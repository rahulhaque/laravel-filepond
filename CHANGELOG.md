# Changelog

All notable changes to `laravel-filepond` will be documented in this file.

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
