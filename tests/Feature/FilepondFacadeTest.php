<?php

namespace RahulHaque\Filepond\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RahulHaque\Filepond\Facades\Filepond;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondFacadeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function can_validate_after_filepond_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 1024, 1024)
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json'
            ]);

        $request = new Request();

        try {
            $request->merge([
                'avatar' => $response->content()
            ])->validate([
                'avatar' => Rule::filepond('required|file|size:30')
            ]);
        } catch (ValidationException $e) {
            $this->assertEquals($e->errors(), ["avatar" => ["The avatar must be 30 kilobytes."]]);
        }
    }

    /** @test */
    function can_get_temporary_file_after_filepond_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $uploadedFile = UploadedFile::fake()->image('avatar.png', 1024, 1024);

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => $uploadedFile
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json'
            ]);

        $temporaryFile = Filepond::field($response->content())->getFile();

        $this->assertEquals($temporaryFile->getSize(), $uploadedFile->getSize());
    }

    /** @test */
    function can_get_data_url_after_filepond_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $uploadedFile = UploadedFile::fake()->image('avatar.png', 50, 50);
        $uploadEncoded = base64_encode($uploadedFile->getContent());

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => $uploadedFile
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json'
            ]);

        $dataUrl = Filepond::field($response->content())->getDataURL();

        $this->assertEquals($uploadEncoded, last(explode(',', $dataUrl)));
    }

    /** @test */
    function can_copy_filepond_file_upload_to_desired_location()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100)
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json'
            ]);

        $fileInfo = Filepond::field($response->content())->copyTo('avatars/avatar-1');

        Storage::disk(config('filepond.disk', 'local'))->assertExists($fileInfo['location']);
    }

    /** @test */
    function can_copy_multiple_filepond_file_upload_to_desired_location()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $request = [];

        // Create 5 temporary file uploads
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($user)
                ->post(route('filepond-process'), [
                    'gallery' => UploadedFile::fake()->image('gallery-'.$i.'.png', 100, 100)
                ], [
                    'Content-Type' => 'multipart/form-data',
                    'accept' => 'application/json'
                ]);

            $request[] = $response->content();
        }

        $fileInfos = Filepond::field($request)->copyTo('galleries/gallery');

        foreach ($fileInfos as $fileInfo) {
            Storage::disk(config('filepond.disk', 'local'))->assertExists($fileInfo['location']);
        }
    }
}