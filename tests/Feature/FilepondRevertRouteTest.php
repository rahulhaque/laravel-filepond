<?php

namespace RahulHaque\Filepond\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondRevertRouteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function can_revert_filepond_file_upload_request()
    {
        $allFiles = Storage::disk(config('filepond.disk'))->allFiles();
        Storage::disk(config('filepond.disk'))->delete($allFiles);

        $user = User::factory()->create();

        $responseAfterProcess = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100)
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json'
            ]);

        $responseAfterRevert = $this
            ->actingAs($user)
            ->call('DELETE', route('filepond-revert'), [], [], [], [], $responseAfterProcess->content());

        $responseAfterRevert->assertStatus(200);
    }
}