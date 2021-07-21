<?php

namespace RahulHaque\Filepond\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Models\Filepond;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondProcessRouteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function can_validate_filepond_file_upload_request()
    {
        $allFiles = Storage::disk(config('filepond.disk'))->allFiles();
        Storage::disk(config('filepond.disk'))->delete($allFiles);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => 'string_input_instead_of_file'
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json'
            ]);

        $response->assertJson(["avatar" => ["The avatar field is required."]]);
    }

    /** @test */
    function can_process_filepond_file_upload_request()
    {
        $allFiles = Storage::disk(config('filepond.disk'))->allFiles();
        Storage::disk(config('filepond.disk'))->delete($allFiles);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100)
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json'
            ]);

        $data = Crypt::decrypt($response->content(), true);

        $fileById = Filepond::find($data['id']);

        Storage::disk(config('filepond.disk'))->assertExists($fileById->filepath);

    }
}