<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RahulHaque\Filepond\Models\Filepond;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;
use RahulHaque\Filepond\Utils\FilepondUtil;

class FilepondRestoreRouteTest extends TestCase
{
    #[Test]
    public function can_restore_filepond_file_upload_request(): void
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('avatar.png', 100, 100);

        $responseAfterProcess = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => $file,
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $serverId = $responseAfterProcess->content();

        $responseAfterRestore = $this
            ->actingAs($user)
            ->get(route('filepond-head', ['restore' => $serverId]));

        $responseAfterRestore->assertStatus(200);
        $responseAfterRestore->assertHeader('Content-Type', 'image/png');
        $responseAfterRestore->assertHeader('Content-Disposition', 'inline; filename=avatar.png');
        $responseAfterRestore->assertHeader('Content-Length', (string) $file->getSize());
        $responseAfterRestore->assertHeader('Access-Control-Expose-Headers', 'Content-Disposition, Content-Length, X-Content-Transfer-Id');
        $this->assertEquals($file->getContent(), $responseAfterRestore->streamedContent());
    }

    #[Test]
    public function cannot_restore_with_missing_token(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('filepond-head', ['restore' => '']));

        $response->assertStatus(400);
    }

    #[Test]
    public function cannot_restore_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('filepond-head', ['restore' => 'invalid-encrypted-token']));

        $response->assertStatus(404);
    }

    #[Test]
    public function cannot_restore_non_existent_record(): void
    {
        $user = User::factory()->create();

        $token = FilepondUtil::makeFilepondId(['id' => 999999]);

        $response = $this
            ->actingAs($user)
            ->get(route('filepond-head', ['restore' => $token]));

        $response->assertStatus(404);
    }

    #[Test]
    public function cannot_restore_when_file_is_missing_on_disk(): void
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $responseAfterProcess = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $serverId = $responseAfterProcess->content();
        $id = FilepondUtil::getFilepondId($serverId);
        $filepond = Filepond::find($id);

        Storage::disk(config('filepond.temp_disk', 'local'))->delete($filepond->filepath);

        $responseAfterRestore = $this
            ->actingAs($user)
            ->get(route('filepond-head', ['restore' => $serverId]));

        $responseAfterRestore->assertStatus(404);
    }
}
