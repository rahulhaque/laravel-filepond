# Laravel FilePond Backend

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rahulhaque/laravel-filepond.svg?style=flat-square)](https://packagist.org/packages/rahulhaque/laravel-filepond)
[![Total Downloads](https://img.shields.io/packagist/dt/rahulhaque/laravel-filepond.svg?style=flat-square)](https://packagist.org/packages/rahulhaque/laravel-filepond)

A straight forward backend support for Laravel application to work with [FilePond](https://pqina.nl/filepond/) file upload javascript library. This package keeps tracks of all the uploaded files and provides an easier interface for the developers to interact with them. It currently features - 

- Single and multiple file uploads.
- Chunk uploads with resume.
- Third party storage support.
- Global server side validation for temporary files.
- Controller/Request level validation before moving the temporary files to permanent location.
- Scheduled artisan command to clean up temporary files and folders after they have expired.
- Can handle filepond's `process`, `patch`, `head`, `revert` and `restore` endpoints.

Support the development with a :star: to let others know it worked for you.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/W7W2I1JIV)

**Demo Projects**

- [Laravel-filepond-vue-inertia-example](https://github.com/rahulhaque/laravel-filepond-vue-inertia-example)

**Video Tutorials:**

- Thanks [ludoguenet](https://github.com/ludoguenet) for featuring my package in - [Créer un système de Drag'n Drop avec Laravel Filepond](https://www.youtube.com/watch?v=IQ3fEseDck8) (in French).

## Documentation

See the corresponding branch for the documentation.

|Version|Branch|
|:-:|:-:|
|Laravel 10|[10.x branch](../../tree/10.x/README.md)|
|Laravel 9|[9.x branch](../../tree/9.x/README.md)|
|Laravel 8|[8.x branch](../../tree/8.x/README.md)|
|Laravel 7|[7.x branch](../../tree/7.x/README.md)|

>**Important:** Please, see the [announcement](../../discussions/50) if you've already installed any previous version of this package. You're requested to update the package with the corresponding Laravel version of your project. I'll be slowly removing any old release as I see fit. Thank you for your support.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email rahulhaque07@gmail.com instead of using the issue tracker.

## Credits

-   [Rahul Haque](https://github.com/rahulhaque)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
