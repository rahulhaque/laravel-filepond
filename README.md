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

## Installation

Laravel 11 users install with.

```bash
composer require rahulhaque/laravel-filepond:"^11.0"
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
            url: "{{ config('filepond.server.url') }}",
            headers: {
                'X-CSRF-TOKEN': "{{ @csrf_token() }}",
            }
        }
    });

    // Create the FilePond instance
    FilePond.create(document.querySelector('input[name="avatar"]'));
    FilePond.create(document.querySelector('input[name="gallery[]"]'), {chunkUploads: true});
</script>
```

Now selecting a file with FilePond input field will upload the file in the temporary directory immediately and append the hidden input in the form. Submit the form to process the uploaded file like below in your controller.

In `UserAvatarController.php` get and process the submitted file by calling the `moveTo()` method from the `Filepond` facade which will return the moved file information as well as delete the file from the temporary storage.

```php
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
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
        // Single and multiple file validation
        $this->validate($request, [
            'avatar' => Rule::filepond([
                'required',
                'image',
                'max:2000'
            ]),
            'gallery.*' => Rule::filepond([
                'required',
                'image',
                'max:2000'
            ])
        ]);
    
        // Set filename
        $avatarName = 'avatar-' . auth()->id();
    
        // Move the file to permanent storage
        // Automatic file extension set
        $fileInfo = Filepond::field($request->avatar)
            ->moveTo('avatars/' . $avatarName);

        // dd($fileInfo);
        // [
        //     "id" => 1,
        //     "dirname" => "avatars",
        //     "basename" => "avatar-1.png",
        //     "extension" => "png",
        //     "filename" => "avatar-1",
        //     "location" => "avatars/avatar-1.png",
        //     "url" => "http://localhost/storage/avatars/avatar-1.png",
        // ];

        $galleryName = 'gallery-' . auth()->id();

        $fileInfos = Filepond::field($request->gallery)
            ->moveTo('galleries/' . $galleryName);
    
        // dd($fileInfos);
        // [
        //     [
        //         "id" => 1,
        //         "dirname" => "galleries",
        //         "basename" => "gallery-1-1.png",
        //         "extension" => "png",
        //         "filename" => "gallery-1-1",
        //         "location" => "galleries/gallery-1-1.png",
        //         "url" => "http://localhost/storage/galleries/gallery-1-1.png",
        //     ],
        //     [
        //         "id" => 2,
        //         "dirname" => "galleries",
        //         "basename" => "gallery-1-2.png",
        //         "extension" => "png",
        //         "filename" => "gallery-1-2",
        //         "location" => "galleries/gallery-1-2.png",
        //         "url" => "http://localhost/storage/galleries/gallery-1-2.png",
        //     ],
        //     [
        //         "id" => 3,
        //         "dirname" => "galleries",
        //         "basename" => "gallery-1-3.png",
        //         "extension" => "png",
        //         "filename" => "gallery-1-3",
        //         "location" => "galleries/gallery-1-3.png",
        //         "url" => "http://localhost/storage/galleries/gallery-1-3.png",
        //     ],
        // ]
    }
}
```

This is the quickest way to get started. This package has already implemented all the classes and controllers for you. Next we will discuss about all the nitty gritty stuffs available.

> **Important:** If you have Laravel debugbar installed, make sure to add `filepond*` in the `except` array of the `./config/debugbar.php` to ignore appending debugbar information.  

## Configuration

First have a look at the `./config/filepond.php` to know about all the options available out of the box. Some important ones mentioned below.

#### Permanent Storage

This package uses Laravel's public filesystem driver for permanent file storage by default. Change the `disk` option to anything you prefer for permanent storage. Hold up! But I am using different disks for different uploads. Don't worry. You will be able to change the disk name on the fly with [copyTo()](https://github.com/rahulhaque/laravel-filepond#copyto) and [moveTo()](https://github.com/rahulhaque/laravel-filepond#moveto) methods.

#### Temporary Storage

This package uses Laravel's local filesystem driver for temporary file storage by default. Change the `temp_disk` and `temp_folder` name to points towards directory for temporary file storage.

> **Note:** Setting temporary file storage to third party will upload the files directly to cloud. On the other hand, you will lose the ability to use controller level validation because the files will not be available in your application server.

#### Validation Rules

Default global server side validation rules can be changed by modifying `validation_rules` array in `./config/filepond.php`. These rules will be applicable to all file uploads by FilePond's `/process` endpoint.

There is also a custom validation rule `Rule::filepond($rules)` is available to validate temporary files before moving them to desired location. Use it with your `FormRequest` class or directly in the controller, whichever you prefer.

#### Middleware

By default, all filepond's routes are protected by `web` and `auth` middleware. Change it if required.

#### Soft Delete

By default `soft_delete` is set to `true` to keep track of all the files uploaded by the users. Set it to false if you want to delete the files with delete request.

## Commands (Cleanup)

This package includes a `php artisan filepond:clear` command to clean up the expired files from the temporary storage. File `expiration` minute can be set in the config file, default is 30 minutes. Add this command to your scheduled command list to run daily. Know more about task scheduling here - [Scheduling Artisan Commands](https://laravel.com/docs/8.x/scheduling#scheduling-artisan-commands)

This command takes a `--all` option which will truncate the `Filepond` model and delete everything inside the temporary storage regardless they are expired or not. This is useful when you lost track of your uploaded files and want to start clean.

> If you see your files are not deleted even after everything is set up correctly, then its probably the directory permission issue. Try setting the permission of filepond's temporary directory to 775 with `sudo chmod -R 775 ./storage/app/filepond/`. And run `php artisan filepond:clear --all` for a clean start (optional). For third party storage like - amazon s3, make sure you have the correct policy set.

### Methods

#### field()

`Filepond::field($field, $checkOwnership)` is a required method which tells the library which FilePond form field to work with. The optional second parameter is to check file ownership of the field. It is useful when an unauthenticated user uploads a file and later tries to retrieve it after authentication. Chain the rest of the methods as required.

#### Rule::filepond($rules)

Use `Rule::filepond($rules)` inside Request class or directly in controller or in custom Validator to validate your filepond fields. See the example.

> **Note:** This method will not work when third party storage is set as your temporary storage. The files are uploaded directly to your third party storage and not available locally for any further modification. Calling this method in such condition will throw error that the file is not found. 

#### copyTo()

Calling the `Filepond::field()->copyTo($pathWithFilename)` method will copy the file from the temporary storage to the path provided along with the filename. It will set the file extension **automatically**. By default the files will be copied to directory relative to config's `disk` option. You can also pass a disk name as **second parameter** if you want to override that. This method will return the copied file info along with `Filepond` model id. For multiple file upload, it will return an array of copied files info. Also note that multiple files will be copied with **trailing incremental** values like `$filename-{$i}`.

#### moveTo()

Calling the `Filepond::field()->moveTo($pathWithFilename)` method works the same way as `copyTo()` method. By default the files will be moved to directory relative to config's `disk` option. You can also pass a disk name as **second parameter** if you want to override that. One thing it does extra for you is delete the temporary file after copying, respecting the value of config's `soft_delete` option for `Filepond` model.

#### delete()

Calling the `Filepond::field()->delete()` method will delete the temporary file respecting the soft delete configuration for `Filepond` model. This method is useful when you're manually handling the file processing using `getFile()` method.

### APIs

If you need more granular approach and know the ins and outs of this package, you may use the below APIs to get the underneath file object and file model to interact with them further. 

#### getFile()

`Filepond::field()->getFile()` method returns the file object same as the Laravel's `$request->file()` object. For multiple uploads, it will return an array of uploaded file objects. You can then process the file manually any way you want.

Processing the file object manually will not update the associated `Filepond` model which is used to keep track of the uploaded files. However the expired files will be cleaned up as usual by the scheduled command. It is recommended that you either call the [delete()](#delete) method or update the underlying model by calling [getModel()](#getModel) method after the processing is done.

> **Note:** This method is not available when third party storage is set as your temporary storage. The files are uploaded directly to your third party storage and not available locally for any further modification. Calling this method in such condition will throw error that the file is not found.

#### getModel()

`Filepond::field()->getModel()` method returns the underlying Laravel `Filepond` model for the given field. This is useful when you have added some custom fields to update in the published migration file for your need.

#### getDataURL()

`Filepond::field()->getDataURL()` method returns the Data URL of uploaded file for the given field just like [HTTP Data URLs](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs). This is useful when you need to store the raw content along with encryption, such as - user signature.

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

## Development

First clone the repo and `cd` into the directory. Build development environment with docker.

```bash
# Build the development image
docker compose build

# Run the development container from image
docker compose up -d

# Drop to development shell
docker compose exec laravel-filepond bash

# Install dependencies
composer install

# Run tests
composer test
```

To free up space after development.

```bash
# To stop the container for later use
docker compose stop

# Stop and remove the container
docker compose down -v

# Also remove the development image if necessary
docker image rm laravel-filepond-development
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
