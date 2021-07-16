# Changelog

All notable changes to `filepond` will be documented in this file

## 1.1.2 - 2021-07-16

- Server side validation option added in `./config/filepond.php`.
- Documentation updated.
- Performance improvements.

## 1.0.2 - 2021-05-22

- Validation message will now include field name.
- Test cases added for filepond process route.
- Reduced package bundle size.

## 1.0.1 - 2021-05-21

- Multiple file uploads will be returned as array of objects as per Laravel standard.
- An issue with temporary storage not being cleared fixed.
- Test cases added for `Filepond` model.

## 1.0.0 - 2021-05-20

- Supports FilePond process and revert routes.
- Built-in artisan command to clean up temporary storage.
- Trait support for tracking user uploads.
