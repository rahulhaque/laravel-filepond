# Laravel FilePond Backend

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rahulhaque/laravel-filepond.svg?style=flat-square)](https://packagist.org/packages/rahulhaque/laravel-filepond)
[![Total Downloads](https://img.shields.io/packagist/dt/rahulhaque/laravel-filepond.svg?style=flat-square)](https://packagist.org/packages/rahulhaque/laravel-filepond)

This package provides a straight forward backend support for Laravel application to work with [FilePond](https://pqina.nl/filepond/) file upload javascript library. Supports both single and multiple file uploads. This package keeps tracks of all the uploaded files and provides an easier interface for the user to interact with the files. It also comes with an artisan command to clean up temporary files after they have expired.

## Installation

Install the package via composer:

```bash
composer require rahulhaque/laravel-filepond
```

### Laravel

Publish the configuration and migration files.

```bash
php artisan vendor:publish --provider="RahulHaque\Filepond\FilepondServiceProvider"
```

Run the migration.

```bash
php artisan migrate
```

## Quickstart

Before we begin, first install and integrate the [FilePond](https://pqina.nl/filepond/docs/) library in your project any way you prefer.

We will make up a scenario for the new-comers to get them started with FilePond right away.

Let's assume we are updating user avatar like the form below.

```html
<form action="{{ route('avatar') }}" method="post">
    <intput type="file" name="avatar" required/>

    <button type="submit">Submit</button>
</form>

<script>
    // Set default FilePond options
    FilePond.setOptions({
        server: {
            process: "{{ config('filepond.server.process') }}",
            revert: "{{ config('filepond.server.revert') }}",
            headers: {
                'X-CSRF-TOKEN': "{{ @csrf_token }}",
            }
        }
    });

    // Create the FilePond instance
    FilePond.create(document.querySelector('input[type="file"]'));
</script>
```

Now in `UserAvatarController.php` get and process the submitted file as below. Here I am using a disk `avatar` for storing the file. We will call the `moveTo` method from the `Filepond` facade which will return the moved file information for further processing along with deleting the file from temporary storage.

```php
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Facades\Filepond;

class UserAvatarController extends Controller
{
    /**
     * Update the avatar for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->validate($request, ['avatar' => 'required']);
    
        $filename = 'avatar-' . auth()->id();
    
        $fileInfo = Filepond::field($request->avatar)
            ->moveTo(Storage::disk('avatar')->path($filename));
    
        auth()->user()->update([
            'avatar' => $fileInfo['basename']
        ]);
    }
}
```

This is the quickest way to get started with FilePond. This package has already implemented all the classes and controllers for you. Next we will talk about all the nitty gritty stuffs available.

## Usage

### Configuration

First have a look at the `./config/filepond.php` to know about all the options available for you out of the box. 

### Temporary Storage

By default this package adds a temporary disk to Laravel's filesystem config named `filepond` which points towards `./storage/app/filepond` directory. You have the freedom to set your own storage disk in the configuration file.

### Command

This package will register a `php artisan filepond:clear` command which will clean up the expired files from the storage upon calling. File expiration minute can be set in the configuration file. Add this command to your scheduled command list to run daily. Detail about task scheduling can be found here - [Scheduling Artisan Commands](https://laravel.com/docs/8.x/scheduling#scheduling-artisan-commands)

You can also pass `--all` option to truncate the `fileponds` table and delete everything inside the temporary storage. This is useful when you lost track of your uploaded files.

### Methods

#### field()

This is a required method which tell the library which FilePond form field to process. This can be single or multiple file upload field.

#### getFile()

Calling the `Filepond::field($field)->getFile()` method will return the request file object the same way as the Laravel returns `$request->file()` object. For multiple upload, it will return a collection of request objects. You can then process the file manually any way you want. All the methods provided by the standard Laravel `$request->file()` object is available there.

**N.B.** Handling the file object manually will not update the associated filepond database model which is used to keep track of the uploaded files. However the expired files will be cleaned up as usual by the scheduled command. It is recommended that you either call the [delete()](#delete) method or update the underlying model by calling [getModel()](#getModel) method after the processing is done.

#### getModel()

Calling the `Filepond::field($field)->getModel()` method will return the underlying Laravel `Filepond` model for the given field. This is useful when you have added some custom fields to update in the published migration file for your need.

#### copyTo()

Calling the `Filepond::field($field)->copyTo($path-with-filename)` method will copy the file from the temporary storage to the path provided along with the filename and will set the file extension automatically. This method will return copied file info along with filepond model id. For multiple file upload, it will return an array of copied files info. Also note that multiple files will be copied with trailing incremental values like `$filename-{$i}`.

#### moveTo()

Calling the `Filepond::field($field)->moveTo($path-with-filename)` method works the same way as copy method. One thing it does extra for you is delete the file after copying, respecting the value of `soft_delete` configuration for `Filepond` model. 

#### delete()

Calling the `Filepond::field($field)->delete()` method will delete the temporary file respecting the value of `soft_delete` configuration for `Filepond` model. This method is useful when you're manually handling the file processing using `getFile()` method.

### Traits

There is a `HasFilepond` trait available to get the temporary files uploaded by the users.

```php
namespace App\Models;

use RahulHaque\Filepond\Traits\HasFilepond;

class User extends Authenticatable
{
    use HasFilepond;
}
```

Now you can get all the file info uploaded by a single user like this.

```php
User::find(1)->fileponds;
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email rahulhaque07@gmail.com instead of using the issue tracker.

## Credits

-   [Rahul Haque](https://github.com/rahulhaque)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
