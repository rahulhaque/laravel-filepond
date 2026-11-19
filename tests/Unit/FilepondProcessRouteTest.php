<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RahulHaque\Filepond\Models\Filepond;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondProcessRouteTest extends TestCase
{
    #[Test]
    public function can_validate_filepond_file_upload_request()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => 'string_input_instead_of_file',
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $response->assertJson(['avatar' => ['The avatar field must be a file.']]);
    }

    #[Test]
    public function can_process_filepond_file_upload_request()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $data = Crypt::decrypt($response->content());

        $fileById = Filepond::find($data['id']);

        Storage::disk(config('filepond.temp_disk', 'local'))->assertExists($fileById->filepath);
    }

    #[Test]
    public function can_process_filepond_file_upload_with_same_name_metadata_part()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $metadata = ['some' => 'value'];

        $response = $this
            ->actingAs($user)
            ->call('POST', route('filepond-process'), [
                'avatar' => json_encode($metadata),
            ], [], [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
            ], $this->transformHeadersToServerVars([
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]));

        $response->assertOk();

        $data = Crypt::decrypt($response->content());

        $fileById = Filepond::find($data['id']);

        Storage::disk(config('filepond.temp_disk', 'local'))->assertExists($fileById->filepath);
        $this->assertSame('avatar.png', $fileById->filename);
        $this->assertSame($metadata, $fileById->metadata);
    }

    #[Test]
    public function can_process_filepond_array_file_upload_request()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'gallery' => ['profile' => UploadedFile::fake()->image('avatar.png', 1024, 1024)],
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $data = Crypt::decrypt($response->content());

        $fileById = Filepond::find($data['id']);

        Storage::disk(config('filepond.temp_disk', 'local'))->assertExists($fileById->filepath);
    }
}
