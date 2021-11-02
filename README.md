# Laravel FilePond Backend

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rahulhaque/laravel-filepond.svg?style=flat-square)](https://packagist.org/packages/rahulhaque/laravel-filepond)
[![Total Downloads](https://img.shields.io/packagist/dt/rahulhaque/laravel-filepond.svg?style=flat-square)](https://packagist.org/packages/rahulhaque/laravel-filepond)

A straight forward backend support for Laravel application to work with [FilePond](https://pqina.nl/filepond/) file upload javascript library. This package keeps tracks of all the uploaded files and provides an easier interface for the user to interact with the files. Supports both single and multiple file uploads. Has options for global server side validation for temporary files along with controller level validation before movine the files to final location. It also comes with an artisan command to clean up temporary files after they have expired.

## Installation

Install the package via composer:

```bash
composer require rahulhaque/laravel-filepond
```

Laravel 7 users use less than 1.x version.

```bash
composer require rahulhaque/laravel-filepond "~0"
```

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

Let's assume we are updating a user avatar and his/her gallery like the form below.

```html
<form action="{{ route('avatar') }}" method="post">
    @csrf
    <!--  For single file upload  -->
    <input type="file" name="avatar" required/>
    <p class="help-block">{{ $errors->first('avatar') }}</p>

    <!--  For multiple file uploads  -->
    <input type="file" name="gallery[]" multiple required/>
    <p class="help-block">{{ $errors->first('gallery.*') }}</p>

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
    FilePond.create(document.querySelector('input[name="avatar"]'));
    FilePond.create(document.querySelector('input[name="gallery[]"]'));
</script>
```

Now selecting a file with FilePond input field will upload the file in the temporary directory immediately and append the hidden input in the form. Submit the form to process the uploaded file like below in your controller.

In `UserAvatarController.php` get and process the submitted file by calling the `moveTo()` method from the `Filepond` facade which will return the moved file information as well as delete the file from the temporary storage.

```php
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        // For single file validation
        Filepond::field($request->avatar)->validate(['avatar' => 'required|image|max:2000']);

        // For multiple file validation
        Filepond::field($request->gallery)->validate(['gallery.*' => 'required|image|max:2000']);
    
        $avatarName = 'avatar-' . auth()->id();
    
        $fileInfo = Filepond::field($request->avatar)
            ->moveTo('/filepath/storage/app/public/avatars/' . $avatarName);

        // dd($fileInfo);
        // [
        //     "id" => 1,
        //     "dirname" => "/filepath/storage/app/public/avatars",
        //     "basename" => "avatar-1.png",
        //     "extension" => "png",
        //     "filename" => "avatar-1",
        // ];

        $galleryName = 'gallery-' . auth()->id();

        $fileInfos = Filepond::field($request->gallery)
            ->moveTo('/filepath/storage/app/public/galleries/' . $galleryName);
    
        // dd($fileInfos);
        // [
        //     [
        //         "id" => 1,
        //         "dirname" => "/filepath/storage/app/public/galleries",
        //         "basename" => "gallery-1-1.png",
        //         "extension" => "png",
        //         "filename" => "gallery-1-1",
        //     ],
        //     [
        //         "id" => 2,
        //         "dirname" => "/filepath/storage/app/public/galleries",
        //         "basename" => "gallery-1-2.jpg",
        //         "extension" => "jpg",
        //         "filename" => "gallery-1-2",
        //     ],
        //     [
        //         "id" => 3,
        //         "dirname" => "/filepath/storage/app/public/galleries",
        //         "basename" => "gallery-1-3.jpg",
        //         "extension" => "jpg",
        //         "filename" => "gallery-1-3",
        //     ],
        // ]
    }
}
```

This is the quickest way to get started. This package has already implemented all the classes and controllers for you. Next we will discuss about all the nitty gritty stuffs available.

> **Important:** If you have Laravel debugbar installed, make sure to add `filepond*` in the `except` array of the `./config/debugbar.php` to ignore appending debugbar information.  

## Configuration

First have a look at the `./config/filepond.php` to know about all the options available out of the box. Some important ones mentioned below.

#### Validation Rules

Default global server side validation rules can be changed by modifying `validation_rules` array in `./config/filepond.php`. These rules will be applicable to FilePond's `/process` route.

#### Temporary Storage

This package adds a disk to Laravel's filesystem config named `filepond` which points towards `./storage/app/filepond` directory for temporary file storage. Set your own if needed.

## Commands (Cleanup)

This package includes a `php artisan filepond:clear` command to clean up the expired files from the temporary storage. File expiration minute can be set in the config file, default is 30 minutes. Add this command to your scheduled command list to run daily. Know more about task scheduling here - [Scheduling Artisan Commands](https://laravel.com/docs/8.x/scheduling#scheduling-artisan-commands)

This command takes `--all` option which will truncate the `Filepond` model and delete everything inside the temporary storage regardless they are expired or not. This is useful when you lost track of your uploaded files and want to start clean.

> If you see your files are not deleted even after everything is set up correctly, then its probably the directory permission issue. Try setting the permission of filepond's temporary directory to 775 with `sudo chmod -R ./storage/app/filepond/`. And run `php artisan filepond:clear --all` for a clean start (optional).

### Methods

#### field()

`Filepond::field($field)` is a required method which tell the library which FilePond form field to work with. Chain the rest of the methods as required.

#### validate()

Calling the `Filepond::field()->validate($rules)` method will validate the temporarily stored file before processing the file further. Supports both single and multiple files validation just as Laravel's default validation for forms.

#### copyTo()

Calling the `Filepond::field()->copyTo($pathWithFilename)` method will copy the file from the temporary storage to the path provided along with the filename and will set the file extension automatically. This method will return the copied file info along with filepond model id. For multiple file upload, it will return an array of copied files info. Also note that multiple files will be copied with trailing incremental values like `$filename-{$i}`.

#### moveTo()

Calling the `Filepond::field()->moveTo($pathWithFilename)` method works the same way as copy method. One thing it does extra for you is delete the file after copying, respecting the value of `soft_delete` configuration for `Filepond` model. 

#### delete()

Calling the `Filepond::field()->delete()` method will delete the temporary file respecting the soft delete configuration for `Filepond` model. This method is useful when you're manually handling the file processing using `getFile()` method.

### APIs

If you need more granular approach and know the ins and outs of this package, you may use the below APIs to get the underneath file object and file model to interact with them further. 

#### getFile()

`Filepond::field()->getFile()` method returns the file object same as the Laravel's `$request->file()` object. For multiple uploads, it will return an array of uploaded file objects. You can then process the file manually any way you want.

> *Note:* Processing the file object manually will not update the associated `Filepond` model which is used to keep track of the uploaded files. However the expired files will be cleaned up as usual by the scheduled command. It is recommended that you either call the [delete()](#delete) method or update the underlying model by calling [getModel()](#getModel) method after the processing is done.

#### getModel()

`Filepond::field()->getModel()` method returns the underlying Laravel `Filepond` model for the given field. This is useful when you have added some custom fields to update in the published migration file for your need.

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

Now you can get all the files info uploaded by a single user like this.

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
