<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FilePond Temporary Disk
    |--------------------------------------------------------------------------
    |
    | Set the FilePond default disk to be used for temporary file storage and
    | cleanup. This disk will be used for temporary file storage and cleared
    | upon running the "artisan filepond:clear" command.
    |
    */
    'disk' => env('FILEPOND_DISK', 'filepond'),

    /*
    |--------------------------------------------------------------------------
    | Laravel Disks Config
    |--------------------------------------------------------------------------
    |
    | This is disk configuration for Laravel ./config/filesystem.php
    |
    */
    'storage' => [
        'driver' => 'local',
        'root' => storage_path('app/filepond'),
    ],

    /*
    |--------------------------------------------------------------------------
    | FilePond Routes Middleware
    |--------------------------------------------------------------------------
    |
    | Default middleware for FilePond routes.
    |
    */
    'middleware' => [
        'web', 'auth'
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Delete FilePond Model
    |--------------------------------------------------------------------------
    |
    | Determine whether to enable or disable soft delete in FilePond model.
    |
    */
    'soft_delete' => true,

    /*
    |--------------------------------------------------------------------------
    | File Delete After (Minutes)
    |--------------------------------------------------------------------------
    |
    | Set the minutes after which the FilePond temporary storage files will
    | be deleted while running 'artisan filepond:clear' command.
    |
    */
    'expiration' => 30,

    /*
    |--------------------------------------------------------------------------
    | FilePond Controller
    |--------------------------------------------------------------------------
    |
    | FilePond controller determines how the requests from FilePond library is
    | processed.
    |
    */
    'controller' => RahulHaque\Filepond\Http\Controllers\FilepondController::class,

    /*
    |--------------------------------------------------------------------------
    | Default Validation Rules
    |--------------------------------------------------------------------------
    |
    | Set the default validation for filepond's ./process route. In other words
    | temporary file upload validation.
    |
    */
    'validation_rules' => [
        'required',
        'file',
        'max:5000'
    ],

    /*
    |--------------------------------------------------------------------------
    | FilePond Server Paths
    |--------------------------------------------------------------------------
    |
    | Configure url for each of the FilePond actions.
    | See details - https://pqina.nl/filepond/docs/patterns/api/server/
    |
    */
    'server' => [
        'process' => env('FILEPOND_PROCESS_URL', '/filepond'),
        'revert' => env('FILEPOND_REVERT_URL', '/filepond'),
    ]
];
