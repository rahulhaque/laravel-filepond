<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RahulHaque\Filepond\Facades\Filepond;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondFacadeTest extends TestCase
{
    #[Test]
    public function can_get_temporary_file_after_file_upload()
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
                'Accept' => 'application/json',
            ]);

        $temporaryFile = Filepond::field($response->content())->getFile();

        $this->assertEquals($temporaryFile->getSize(), $uploadedFile->getSize());
    }

    #[Test]
    public function can_get_temporary_file_after_chunk_file_upload(): void
    {
        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('chunk_upload_file_to_local');

        $user = User::factory()->create();

        $content = str_repeat('f', 1 * 1024 * 1024); // Fake content 1MB (1048576 Bytes)
        $chunks = str_split($content, 128 * 1024); // Split into 8 chunks 128KB (131072 Bytes)

        $initChunkUploadResponse = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [], [
                'Upload-Length' => strlen($content),
            ]);

        $initChunkUploadResponse->assertSuccessful();

        $serverId = $initChunkUploadResponse->content();

        $uploadOffset = 0;

        foreach ($chunks as $chunk) {
            $response = $this
                ->actingAs($user)
                ->call(
                    method: 'PATCH',
                    uri: route('filepond-patch', ['patch' => $serverId]),
                    content: $chunk,
                    server: $this->transformHeadersToServerVars([
                        'Content-Length' => strlen($chunk),
                        'Upload-Length' => strlen($content),
                        'Upload-Name' => 'test-file.txt',
                        'Upload-Offset' => $uploadOffset,
                        'Content-Type' => 'application/offset+octet-stream',
                    ])
                );

            $response->assertSuccessful();

            if ($response->getContent() === 'Ok') {
                $uploadOffset = (int) $response->headers->get('Upload-Offset');

                continue;
            }

            break;
        }

        $temporaryFile = Filepond::field($serverId)->getFile();

        $this->assertEquals(strlen($content), $temporaryFile->getSize());
    }

    #[Test]
    public function can_get_metadata_after_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $uploadedFile = UploadedFile::fake()->image('avatar.png', 1024, 1024);

        $metadata = ['some' => 'value'];

        $response = $this
            ->actingAs($user)
            ->call(
                method: 'POST',
                uri: route('filepond-process'),
                parameters: ['avatar' => json_encode($metadata)],
                files: ['avatar' => $uploadedFile],
                server: $this->transformHeadersToServerVars([
                    'Content-Type' => 'multipart/form-data',
                    'Accept' => 'application/json',
                ])
            );

        $responseMetadata = Filepond::field($response->content())->getMetadata();

        $this->assertEquals($metadata, $responseMetadata);
    }

    #[Test]
    public function can_get_metadata_after_multiple_file_upload()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $responses = [];
        $metadatas = [];

        // Create 5 temporary file uploads
        for ($i = 1; $i <= 5; $i++) {
            $metadata = ['some' => 'value-'.$i];

            $response = $this
                ->actingAs($user)
                ->call(
                    method: 'POST',
                    uri: route('filepond-process'),
                    parameters: ['gallery' => json_encode($metadata)],
                    files: ['gallery' => UploadedFile::fake()->image('gallery-'.$i.'.png', 1024, 1024)],
                    server: $this->transformHeadersToServerVars([
                        'Content-Type' => 'multipart/form-data',
                        'Accept' => 'application/json',
                    ])
                );

            $responses[] = $response->content();
            $metadatas[] = $metadata;
        }

        $responseMetadata = Filepond::field($responses)->getMetadata();

        $this->assertEquals($metadatas, $responseMetadata);
    }

    #[Test]
    public function can_get_metadata_after_chunk_file_upload(): void
    {
        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('chunk_upload_file_to_local');

        $user = User::factory()->create();

        $content = str_repeat('f', 1 * 1024 * 1024); // Fake content 1MB (1048576 Bytes)
        $chunks = str_split($content, 128 * 1024); // Split into 8 chunks 128KB (131072 Bytes)

        $metadata = ['some' => 'value'];

        $initChunkUploadResponse = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => json_encode($metadata),
            ], [
                'Upload-Length' => strlen($content),
            ]);

        $initChunkUploadResponse->assertSuccessful();

        $serverId = $initChunkUploadResponse->content();

        $uploadOffset = 0;

        foreach ($chunks as $chunk) {
            $response = $this
                ->actingAs($user)
                ->call(
                    method: 'PATCH',
                    uri: route('filepond-patch', ['patch' => $serverId]),
                    content: $chunk,
                    server: $this->transformHeadersToServerVars([
                        'Content-Length' => strlen($chunk),
                        'Upload-Length' => strlen($content),
                        'Upload-Name' => 'test-file.txt',
                        'Upload-Offset' => $uploadOffset,
                        'Content-Type' => 'application/offset+octet-stream',
                    ])
                );

            $response->assertSuccessful();

            if ($response->getContent() === 'Ok') {
                $uploadOffset = (int) $response->headers->get('Upload-Offset');

                continue;
            }

            break;
        }

        $responseMetadata = Filepond::field($serverId)->getMetadata();

        $this->assertEquals($metadata, $responseMetadata);
    }

    #[Test]
    public function can_copy_file_upload_to_desired_location()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 1024, 1024),
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $fileInfo = Filepond::field($response->content())->copyTo('avatars/avatar-1');

        Storage::disk(config('filepond.disk', 'local'))->assertExists($fileInfo['location']);
    }

    #[Test]
    public function can_move_file_upload_to_desired_location()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 1024, 1024),
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $fileInfo = Filepond::field($response->content())->moveTo('avatars/avatar-1');

        Storage::disk(config('filepond.disk', 'local'))->assertExists($fileInfo['location']);
    }

    #[Test]
    public function can_copy_multiple_file_upload_to_desired_location()
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
                    'Accept' => 'application/json',
                ]);

            $request[] = $response->content();
        }

        $fileInfos = Filepond::field($request)->copyTo('galleries/gallery');

        foreach ($fileInfos as $fileInfo) {
            Storage::disk(config('filepond.disk', 'local'))->assertExists($fileInfo['location']);
        }
    }

    #[Test]
    public function can_move_multiple_file_upload_to_desired_location()
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
                    'Accept' => 'application/json',
                ]);

            $request[] = $response->content();
        }

        $fileInfos = Filepond::field($request)->moveTo('galleries/gallery');

        foreach ($fileInfos as $fileInfo) {
            Storage::disk(config('filepond.disk', 'local'))->assertExists($fileInfo['location']);
        }
    }
}
