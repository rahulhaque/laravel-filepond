# Changelog

All notable changes to `laravel-filepond` will be documented in this file.

## 13.2.2 - 2026-11-03

- Added `Filepond::field()->preview()` helper method to preview temporary uploads after form refresh. ✨
- Added `@filepondPreview()` blade directive, does the same as above with less boilerplate. ✨
- Restore route now streams file response instead of loading into memory. 🚀
- Restore route enforces `max_previewable_file_size` server-side. 🐛
- Restore route input validation and error handling improved. 🐛
- New test cases added to support above changes. 🧪

## 13.1.2 - 2026-09-09

- Addressed Laravel HTTP request changes in [v13.25.0](https://github.com/laravel/framework/issues/61356). 🐛
- Addressed Laravel storage path normalization changes in 13.30.1. 🐛
- Local chunk upload performance improvement. 🚀
- New test cases added to support above changes. 🧪

## 13.1.1 - 2026-03-30

- Support for [filepond-plugin-file-metadata](https://pqina.nl/filepond/docs/api/plugins/file-metadata/) added. ✨
- New method `Filepond::field()->getMetadata()` added. ✨
- New test cases added to support above changes. 🧪

## 13.0.1 - 2026-03-28

- Mimetype missing after chunk upload fixed. 🐛

## 13.0.0 - 2026-03-18

- Laravel 13 support added. ✨
- Filepond migration proper indexing added. 📋
- Experimental `getDataURL()` method removed. 🧹
- Deprecated method from validation removed. 🧽
- Made the package lightweight as possible. 🪶
- Typo in whole database migration fixed. ✍️
