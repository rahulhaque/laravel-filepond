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

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('move_file_local_to_local');

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

        $fileInfo = Filepond::field($response->content())->moveTo($pathToMove);

        Storage::disk(config('filepond.disk'))->assertExists($fileInfo['location']);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_move_file_external_to_external()
    {
        Config::set('filepond.temp_disk', 's3');
        Config::set('filepond.disk', 's3');

        $pathToMove = 'move_file_external_to_external/avatar';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('move_file_external_to_external');

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

        $fileInfo = Filepond::field($response->content())->moveTo($pathToMove);

        Storage::disk(config('filepond.disk'))->assertExists($fileInfo['location']);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_move_file_local_to_external()
    {
        Config::set('filepond.disk', 's3');

        $pathToMove = 'move_file_local_to_external/avatar';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('move_file_local_to_external');

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

        $fileInfo = Filepond::field($response->content())->moveTo($pathToMove);

        Storage::disk(config('filepond.disk'))->assertExists($fileInfo['location']);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_move_file_external_to_local()
    {
        Config::set('filepond.temp_disk', 's3');

        $pathToMove = 'move_file_external_to_local/avatar';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('move_file_external_to_local');

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

        $fileInfo = Filepond::field($response->content())->moveTo($pathToMove);

        Storage::disk(config('filepond.disk'))->assertExists($fileInfo['location']);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_chunk_upload_file_to_local(): void
    {
        $pathToMove = 'chunk_upload_file_to_local/document';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('chunk_upload_file_to_local');

        $user = User::factory()->create();

        $content = str_repeat('f', 2 * 1024 * 1024); // Fake content 2MB (2097152 Bytes)
        $chunks = mb_str_split($content, 256 * 1024); // Split into 8 chunks 256KB (262144 Bytes)

        $initChunkUploadResponse = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [], [
                'upload-length' => mb_strlen($content),
            ]);

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
                        'content-length' => mb_strlen($chunk),
                        'upload-length' => mb_strlen($content),
                        'upload-name' => 'test-file.txt',
                        'upload-offset' => $uploadOffset,
                        'content-type' => 'application/offset+octet-stream',
                    ])
                );

            if ($response->getContent() === 'Ok') {
                $uploadOffset = (int) $response->headers->get('upload-offset');
            }
        }

        $fileInfo = Filepond::field($serverId)->moveTo($pathToMove);

        Storage::disk(config('filepond.disk'))->assertExists($fileInfo['location']);
    }

    #[Test]
    #[Group('disk-test')]
    public function can_chunk_upload_file_to_external(): void
    {
        Config::set('filepond.temp_disk', 's3');
        Config::set('filepond.disk', 's3');

        $pathToMove = 'chunk_upload_file_to_external/document';

        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        Storage::disk(config('filepond.disk'))->deleteDirectory('chunk_upload_file_to_external');

        $user = User::factory()->create();

        $content = str_repeat('f', 2 * 1024 * 1024); // Fake content 2MB (2097152 Bytes)
        $chunks = mb_str_split($content, 256 * 1024); // Split into 8 chunks 256KB (262144 Bytes)

        $initChunkUploadResponse = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [], [
                'upload-length' => mb_strlen($content),
            ]);

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
                        'content-length' => mb_strlen($chunk),
                        'upload-length' => mb_strlen($content),
                        'upload-name' => 'test-file.txt',
                        'upload-offset' => $uploadOffset,
                        'content-type' => 'application/offset+octet-stream',
                    ])
                );

            if ($response->getContent() === 'Ok') {
                $uploadOffset = (int) $response->headers->get('upload-offset');
            }
        }

        $fileInfo = Filepond::field($serverId)->moveTo($pathToMove);

        Storage::disk(config('filepond.disk'))->assertExists($fileInfo['location']);
    }
}
