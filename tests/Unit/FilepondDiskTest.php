<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RahulHaque\Filepond\Facades\Filepond;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondDiskTest extends TestCase
{
    #[Test]
    #[Group('disk-test')]
    public function can_move_file_local_to_local()
    {
        $pathToMove = 'move_file_local_to_local/avatar';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('move_file_local_to_local');

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

        $fileInfo = Filepond::field($response->content())->moveTo($pathToMove);

        $size = Storage::disk(config('filepond.disk'))->size($fileInfo['location']);

        $this->assertEquals($uploadedFile->getSize(), $size);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_move_file_external_to_external()
    {
        Config::set('filepond.temp_disk', 's3');
        Config::set('filepond.disk', 's3');

        $pathToMove = 'move_file_external_to_external/avatar';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('move_file_external_to_external');

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

        $fileInfo = Filepond::field($response->content())->moveTo($pathToMove);

        $size = Storage::disk(config('filepond.disk'))->size($fileInfo['location']);

        $this->assertEquals($uploadedFile->getSize(), $size);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_move_file_local_to_external()
    {
        Config::set('filepond.disk', 's3');

        $pathToMove = 'move_file_local_to_external/avatar';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('move_file_local_to_external');

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

        $fileInfo = Filepond::field($response->content())->moveTo($pathToMove);

        $size = Storage::disk(config('filepond.disk'))->size($fileInfo['location']);

        $this->assertEquals($uploadedFile->getSize(), $size);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_move_file_external_to_local()
    {
        Config::set('filepond.temp_disk', 's3');

        $pathToMove = 'move_file_external_to_local/avatar';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('move_file_external_to_local');

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

        $fileInfo = Filepond::field($response->content())->moveTo($pathToMove);

        $size = Storage::disk(config('filepond.disk'))->size($fileInfo['location']);

        $this->assertEquals($uploadedFile->getSize(), $size);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_chunk_upload_file_to_local(): void
    {
        $pathToMove = 'chunk_upload_file_to_local/document';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('chunk_upload_file_to_local');

        $user = User::factory()->create();

        $content = str_repeat('f', 1 * 1024 * 1024); // Fake content 1MB (1048576 Bytes)
        $chunks = mb_str_split($content, 128 * 1024); // Split into 8 chunks 128KB (131072 Bytes)

        $initChunkUploadResponse = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [], [
                'Upload-Length' => mb_strlen($content),
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
                        'Content-Length' => mb_strlen($chunk),
                        'Upload-Length' => mb_strlen($content),
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

        $fileInfo = Filepond::field($serverId)->moveTo($pathToMove);

        $size = Storage::disk(config('filepond.disk'))->size($fileInfo['location']);

        $this->assertEquals(mb_strlen($content), $size);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_chunk_upload_file_to_external(): void
    {
        Config::set('filepond.temp_disk', 's3');
        Config::set('filepond.disk', 's3');

        $pathToMove = 'chunk_upload_file_to_external/document';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('chunk_upload_file_to_external');

        $user = User::factory()->create();

        $content = str_repeat('f', 23 * 1024 * 1024); // Fake content 23MB
        $chunks = mb_str_split($content, 5 * 1024 * 1024); // Split into 5 chunks 5MB

        $initChunkUploadResponse = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [], [
                'Upload-Length' => mb_strlen($content),
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
                        'Content-Length' => mb_strlen($chunk),
                        'Upload-Length' => mb_strlen($content),
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

        $fileInfo = Filepond::field($serverId)->moveTo($pathToMove);

        $size = Storage::disk(config('filepond.disk'))->size($fileInfo['location']);

        $this->assertEquals(mb_strlen($content), $size);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_resume_chunk_upload_from_local(): void
    {
        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder'));

        $user = User::factory()->create();

        $content = str_repeat('f', 1 * 1024 * 1024); // Fake content 1MB (1048576 Bytes)
        $chunks = mb_str_split($content, 128 * 1024); // Split into 8 chunks 128KB (131072 Bytes)

        $initChunkUploadResponse = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [], [
                'Upload-Length' => mb_strlen($content),
            ]);

        $initChunkUploadResponse->assertSuccessful();

        $serverId = $initChunkUploadResponse->content();

        $uploadOffset = 0;

        foreach ($chunks as $index => $chunk) {
            $response = $this
                ->actingAs($user)
                ->call(
                    method: 'PATCH',
                    uri: route('filepond-patch', ['patch' => $serverId]),
                    content: $chunk,
                    server: $this->transformHeadersToServerVars([
                        'Content-Length' => mb_strlen($chunk),
                        'Upload-Length' => mb_strlen($content),
                        'Upload-Name' => 'test-file.txt',
                        'Upload-Offset' => $uploadOffset,
                        'Content-Type' => 'application/offset+octet-stream',
                    ])
                );

            $response->assertSuccessful();

            if ($response->getContent() === 'Ok') {
                $uploadOffset = (int) $response->headers->get('Upload-Offset');

                // Stop upload on chunk 4
                if ($index === 3) {
                    break;
                }

                continue;
            }

            break;
        }

        $offsetResponse = $this
            ->actingAs($user)
            ->head(route('filepond-patch', ['patch' => $serverId]));

        $offsetResponse->assertHeader('Upload-Offset', $uploadOffset);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_resume_chunk_upload_from_external(): void
    {
        Config::set('filepond.temp_disk', 's3');
        Config::set('filepond.disk', 's3');

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder'));

        $user = User::factory()->create();

        // Large chunks
        $content = str_repeat('f', 23 * 1024 * 1024); // Fake content 23MB
        $chunks = mb_str_split($content, 5 * 1024 * 1024); // Split into 5 chunks 5MB

        $initChunkUploadResponse = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [], [
                'Upload-Length' => mb_strlen($content),
            ]);

        $initChunkUploadResponse->assertSuccessful();

        $serverId = $initChunkUploadResponse->content();

        $uploadOffset = 0;

        foreach ($chunks as $index => $chunk) {
            $response = $this
                ->actingAs($user)
                ->call(
                    method: 'PATCH',
                    uri: route('filepond-patch', ['patch' => $serverId]),
                    content: $chunk,
                    server: $this->transformHeadersToServerVars([
                        'Content-Length' => mb_strlen($chunk),
                        'Upload-Length' => mb_strlen($content),
                        'Upload-Name' => 'test-file.txt',
                        'Upload-Offset' => $uploadOffset,
                        'Content-Type' => 'application/offset+octet-stream',
                    ])
                );

            $response->assertSuccessful();

            if ($response->getContent() === 'Ok') {
                $uploadOffset = (int) $response->headers->get('Upload-Offset');

                // Stop upload on chunk 3
                if ($index === 2) {
                    break;
                }

                continue;
            }

            break;
        }

        $offsetResponse = $this
            ->actingAs($user)
            ->head(route('filepond-patch', ['patch' => $serverId]));

        $offsetResponse->assertHeader('Upload-Offset', $uploadOffset);
    }
}
