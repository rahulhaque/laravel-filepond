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
                'X-CSRF-TOKEN': "{{ @csrf_token() }}",
            }
        }
    });

    // Create the FilePond instance
    FilePond.create(document.querySelector('input[type="file"]'));
</script>
```

Now selecting a file with FilePond input field which will upload the file in the temporary directory right away and append the hidden input in the form. Submit the form to process the uploaded file like below in your controller.

In `UserAvatarController.php` get and process the submitted file by calling the `moveTo()` method from the `Filepond` facade which will return the moved file information as well as delete the file from the temporary storage.

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

This is the quickest way to get started. This package has already implemented all the classes and controllers for you. Next we will discuss about all the nitty gritty stuffs available.

## Usage

### Configuration

First have a look at the `./config/filepond.php` to know about all the options available out of the box.

### Temporary Storage

This package adds a disk to Laravel's filesystem config named `filepond` which points towards `./storage/app/filepond` directory for temporary file storage. Set your own if needed.

### Command

This package includes a `php artisan filepond:clear` command to clean up the expired files from the temporary storage. File expiration minute can be set in the config file, default is 30 minutes. Add this command to your scheduled command list to run daily. Know more about task scheduling here - [Scheduling Artisan Commands](https://laravel.com/docs/8.x/scheduling#scheduling-artisan-commands)

This command takes `--all` option which will truncate the `Filepond` model and delete everything inside the temporary storage regardless they are expired or not. This is useful when you lost track of your uploaded files and want to start clean.

### Methods

#### field()

`Filepond::field($field)` is a required method which tell the library which FilePond form field to work with. Chain the rest of the methods as required.

#### getFile()

`Filepond::field()->getFile()` method returns the file object same as the Laravel's `$request->file()` object. For multiple uploads, it will return an array of uploaded file objects. You can then process the file manually any way you want.

*Note:* Processing the file object manually will not update the associated `Filepond` model which is used to keep track of the uploaded files. However the expired files will be cleaned up as usual by the scheduled command. It is recommended that you either call the [delete()](#delete) method or update the underlying model by calling [getModel()](#getModel) method after the processing is done.

#### getModel()

`Filepond::field()->getModel()` method returns the underlying Laravel `Filepond` model for the given field. This is useful when you have added some custom fields to update in the published migration file for your need.

#### copyTo()

Calling the `Filepond::field()->copyTo($path-with-filename)` method will copy the file from the temporary storage to the path provided along with the filename and will set the file extension automatically. This method will return copied file info along with filepond model id. For multiple file upload, it will return an array of copied files info. Also note that multiple files will be copied with trailing incremental values like `$filename-{$i}`.

#### moveTo()

Calling the `Filepond::field()->moveTo($path-with-filename)` method works the same way as copy method. One thing it does extra for you is delete the file after copying, respecting the value of `soft_delete` configuration for `Filepond` model. 

#### delete()

Calling the `Filepond::field()->delete()` method will delete the temporary file respecting the soft delete configuration for `Filepond` model. This method is useful when you're manually handling the file processing using `getFile()` method.

### Traits

There is a `HasFilepond` trait available to get the temporary files uploaded by the users.

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
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

## Testing

```bash
composer test
```

## Changelog

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
