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
    public function can_validate_null_filepond_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $request = new Request([
            'avatar' => null,
        ]);

        try {
            $request->validate([
                'avatar' => Rule::filepond('required|image|mimes:jpg|size:30'),
            ]);
        } catch (ValidationException $e) {
            $this->assertEquals($e->errors(), ['avatar' => ['The avatar field is required.']]);
        }
    }

    /** @test */
    public function can_validate_after_filepond_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 1024, 1024),
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json',
            ]);

        $request = new Request([
            'avatar' => $response->content(),
        ]);

        try {
            $request->validate([
                'avatar' => Rule::filepond('required|image|mimes:jpg|size:30'),
            ]);
        } catch (ValidationException $e) {
            $this->assertEquals($e->errors(), [
                'avatar' => [
                    'The avatar must be a file of type: jpg.',
                    'The avatar must be 30 kilobytes.',
                ],
            ]);
        }
    }

    /** @test */
    public function can_validate_after_multiple_filepond_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $responses = [];

        // Create 5 temporary file uploads
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($user)
                ->post(route('filepond-process'), [
                    'gallery' => UploadedFile::fake()->image('gallery-'.$i.'.png', 1024, 1024),
                ], [
                    'Content-Type' => 'multipart/form-data',
                    'accept' => 'application/json',
                ]);

            $responses[] = $response->content();
        }

        $request = new Request([
            'gallery' => $responses,
        ]);

        try {
            $request->validate([
                'gallery.*' => Rule::filepond('required|image|mimes:jpg|size:30'),
            ]);
        } catch (ValidationException $e) {
            $this->assertEquals($e->errors(), [
                'gallery.0' => [
                    'The gallery.0 must be a file of type: jpg.',
                    'The gallery.0 must be 30 kilobytes.',
                ],
                'gallery.1' => [
                    'The gallery.1 must be a file of type: jpg.',
                    'The gallery.1 must be 30 kilobytes.',
                ],
                'gallery.2' => [
                    'The gallery.2 must be a file of type: jpg.',
                    'The gallery.2 must be 30 kilobytes.',
                ],
                'gallery.3' => [
                    'The gallery.3 must be a file of type: jpg.',
                    'The gallery.3 must be 30 kilobytes.',
                ],
                'gallery.4' => [
                    'The gallery.4 must be a file of type: jpg.',
                    'The gallery.4 must be 30 kilobytes.',
                ],
            ]);
        }
    }

    /** @test */
    public function can_validate_after_nested_multiple_filepond_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $responses = [];

        // Create 5 temporary file uploads
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($user)
                ->post(route('filepond-process'), [
                    'galleries' => UploadedFile::fake()->image('gallery-'.$i.'.png', 1024, 1024),
                ], [
                    'Content-Type' => 'multipart/form-data',
                    'accept' => 'application/json',
                ]);

            $responses[] = [
                'title' => 'test-'.$i,
                'image' => $response->content(),
            ];
        }

        $request = new Request([
            'galleries' => $responses,
        ]);

        try {
            $request->validate([
                'galleries.*.image' => Rule::filepond('required|image|mimes:jpg|size:30'),
            ]);
        } catch (ValidationException $e) {
            $this->assertEquals($e->errors(), [
                'galleries.0.image' => [
                    'The galleries.0.image must be a file of type: jpg.',
                    'The galleries.0.image must be 30 kilobytes.',
                ],
                'galleries.1.image' => [
                    'The galleries.1.image must be a file of type: jpg.',
                    'The galleries.1.image must be 30 kilobytes.',
                ],
                'galleries.2.image' => [
                    'The galleries.2.image must be a file of type: jpg.',
                    'The galleries.2.image must be 30 kilobytes.',
                ],
                'galleries.3.image' => [
                    'The galleries.3.image must be a file of type: jpg.',
                    'The galleries.3.image must be 30 kilobytes.',
                ],
                'galleries.4.image' => [
                    'The galleries.4.image must be a file of type: jpg.',
                    'The galleries.4.image must be 30 kilobytes.',
                ],
            ]);
        }
    }

    /** @test */
    public function can_get_temporary_file_after_filepond_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $uploadedFile = UploadedFile::fake()->image('avatar.png', 1024, 1024);

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => $uploadedFile,
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json',
            ]);

        $temporaryFile = Filepond::field($response->content())->getFile();

        $this->assertEquals($temporaryFile->getSize(), $uploadedFile->getSize());
    }

    /** @test */
    public function can_get_data_url_after_filepond_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $uploadedFile = UploadedFile::fake()->image('avatar.png', 50, 50);
        $uploadEncoded = base64_encode($uploadedFile->getContent());

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => $uploadedFile,
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json',
            ]);

        $dataUrl = Filepond::field($response->content())->getDataURL();

        $this->assertEquals($uploadEncoded, last(explode(',', $dataUrl)));
    }

    /** @test */
    public function can_copy_filepond_file_upload_to_desired_location()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 1024, 1024),
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json',
            ]);

        $fileInfo = Filepond::field($response->content())->copyTo('avatars/avatar-1');

        Storage::disk(config('filepond.disk', 'local'))->assertExists($fileInfo['location']);
    }

    /** @test */
    public function can_move_filepond_file_upload_to_desired_location()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 1024, 1024),
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json',
            ]);

        $fileInfo = Filepond::field($response->content())->moveTo('avatars/avatar-1');

        Storage::disk(config('filepond.disk', 'local'))->assertExists($fileInfo['location']);
    }

    /** @test */
    public function can_copy_multiple_filepond_file_upload_to_desired_location()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $request = [];

        // Create 5 temporary file uploads
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($user)
                ->post(route('filepond-process'), [
                    'gallery' => UploadedFile::fake()->image('gallery-'.$i.'.png', 1024, 1024),
                ], [
                    'Content-Type' => 'multipart/form-data',
                    'accept' => 'application/json',
                ]);

            $request[] = $response->content();
        }

        $fileInfos = Filepond::field($request)->copyTo('galleries/gallery');

        foreach ($fileInfos as $fileInfo) {
            Storage::disk(config('filepond.disk', 'local'))->assertExists($fileInfo['location']);
        }
    }

    /** @test */
    public function can_move_multiple_filepond_file_upload_to_desired_location()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $request = [];

        // Create 5 temporary file uploads
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->actingAs($user)
                ->post(route('filepond-process'), [
                    'gallery' => UploadedFile::fake()->image('gallery-'.$i.'.png', 1024, 1024),
                ], [
                    'Content-Type' => 'multipart/form-data',
                    'accept' => 'application/json',
                ]);

            $request[] = $response->content();
        }

        $fileInfos = Filepond::field($request)->moveTo('galleries/gallery');

        foreach ($fileInfos as $fileInfo) {
            Storage::disk(config('filepond.disk', 'local'))->assertExists($fileInfo['location']);
        }
    }
}
