<?php

namespace RahulHaque\Filepond\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondValidationTest extends TestCase
{
    #[Test]
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

    #[Test]
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
                    'The avatar field must be a file of type: jpg.',
                    'The avatar field must be 30 kilobytes.',
                ],
            ]);
        }
    }

    #[Test]
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
                    'The gallery.0 field must be a file of type: jpg.',
                    'The gallery.0 field must be 30 kilobytes.',
                ],
                'gallery.1' => [
                    'The gallery.1 field must be a file of type: jpg.',
                    'The gallery.1 field must be 30 kilobytes.',
                ],
                'gallery.2' => [
                    'The gallery.2 field must be a file of type: jpg.',
                    'The gallery.2 field must be 30 kilobytes.',
                ],
                'gallery.3' => [
                    'The gallery.3 field must be a file of type: jpg.',
                    'The gallery.3 field must be 30 kilobytes.',
                ],
                'gallery.4' => [
                    'The gallery.4 field must be a file of type: jpg.',
                    'The gallery.4 field must be 30 kilobytes.',
                ],
            ]);
        }
    }

    #[Test]
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
                'title' => fake()->name(),
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
                    'The galleries.0.image field must be a file of type: jpg.',
                    'The galleries.0.image field must be 30 kilobytes.',
                ],
                'galleries.1.image' => [
                    'The galleries.1.image field must be a file of type: jpg.',
                    'The galleries.1.image field must be 30 kilobytes.',
                ],
                'galleries.2.image' => [
                    'The galleries.2.image field must be a file of type: jpg.',
                    'The galleries.2.image field must be 30 kilobytes.',
                ],
                'galleries.3.image' => [
                    'The galleries.3.image field must be a file of type: jpg.',
                    'The galleries.3.image field must be 30 kilobytes.',
                ],
                'galleries.4.image' => [
                    'The galleries.4.image field must be a file of type: jpg.',
                    'The galleries.4.image field must be 30 kilobytes.',
                ],
            ]);
        }
    }
}
