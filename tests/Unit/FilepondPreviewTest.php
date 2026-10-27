<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Tests\Unit;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
use PHPUnit\Framework\Attributes\Test;
use RahulHaque\Filepond\Facades\Filepond;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;
use RahulHaque\Filepond\Utils\FilepondUtil;

class FilepondPreviewTest extends TestCase
{
    #[Test]
    public function can_return_empty_array_for_null(): void
    {
        $result = Filepond::field(null)->preview();

        $this->assertInstanceOf(Js::class, $result);
        $this->assertEquals('[]', (string) $result);
    }

    #[Test]
    public function can_return_empty_array_for_empty_string(): void
    {
        $result = Filepond::field('')->preview();

        $this->assertEquals('[]', (string) $result);
    }

    #[Test]
    public function can_return_empty_array_for_empty_array(): void
    {
        $result = Filepond::field([])->preview();

        $this->assertEquals('[]', (string) $result);
    }

    #[Test]
    public function cannot_preview_with_invalid_token(): void
    {
        $this->expectException(DecryptException::class);

        Filepond::field('invalid-encrypted-token')->preview();
    }

    #[Test]
    public function can_return_limbo_file_entry_for_valid_upload(): void
    {
        Storage::disk(config('filepond.temp_disk', 'local'))
            ->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.png', 100, 100);

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => $file,
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $serverId = $response->content();

        $result = $this->decodeJs(Filepond::field($serverId)->preview());

        $this->assertCount(1, $result);
        $this->assertEquals(
            FilepondUtil::getFilepondId($serverId),
            FilepondUtil::getFilepondId($result[0]['source'])
        );
        $this->assertEquals('limbo', $result[0]['options']['type']);
        $this->assertEquals('avatar.png', $result[0]['options']['file']['name']);
        $this->assertEquals('image/png', $result[0]['options']['file']['type']);
        $this->assertIsInt($result[0]['options']['file']['size']);
        $this->assertGreaterThan(0, $result[0]['options']['file']['size']);
    }

    #[Test]
    public function can_return_multiple_limbo_file_entries(): void
    {
        Storage::disk(config('filepond.temp_disk', 'local'))
            ->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $serverIds = [];
        foreach (['photo1.png', 'photo2.png'] as $filename) {
            $response = $this
                ->actingAs($user)
                ->post(route('filepond-process'), [
                    'gallery' => UploadedFile::fake()->image($filename, 50, 50),
                ], [
                    'Content-Type' => 'multipart/form-data',
                    'Accept' => 'application/json',
                ]);
            $serverIds[] = $response->content();
        }

        $result = $this->decodeJs(Filepond::field($serverIds)->preview());

        $this->assertCount(2, $result);
        foreach ($result as $i => $entry) {
            $this->assertEquals(
                FilepondUtil::getFilepondId($serverIds[$i]),
                FilepondUtil::getFilepondId($entry['source'])
            );
            $this->assertEquals('limbo', $entry['options']['type']);
        }
    }

    #[Test]
    public function can_fall_back_to_local_type_when_exceeding_max_preview_size(): void
    {
        Storage::disk(config('filepond.temp_disk', 'local'))
            ->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('large-video.mp4', str_repeat('a', 5000));

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'video' => $file,
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $serverId = $response->content();

        // Set max size to 1KB — file is 5KB so it should fall back to 'local'
        config(['filepond.max_previewable_file_size' => 1]);

        $result = $this->decodeJs(Filepond::field($serverId)->preview());

        $this->assertCount(1, $result);
        $this->assertEquals(
            FilepondUtil::getFilepondId($serverId),
            FilepondUtil::getFilepondId($result[0]['source'])
        );
        $this->assertEquals('local', $result[0]['options']['type']);
        $this->assertEquals('large-video.mp4', $result[0]['options']['file']['name']);
        $this->assertIsInt($result[0]['options']['file']['size']);
        $this->assertEquals(5000, $result[0]['options']['file']['size']);
    }

    #[Test]
    public function can_use_limbo_when_no_max_size_configured(): void
    {
        Storage::disk(config('filepond.temp_disk', 'local'))
            ->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('large-video.mp4', str_repeat('a', 5000));

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'video' => $file,
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $serverId = $response->content();

        config(['filepond.max_previewable_file_size' => null]);

        $result = $this->decodeJs(Filepond::field($serverId)->preview());

        $this->assertCount(1, $result);
        $this->assertEquals('limbo', $result[0]['options']['type']);
    }

    #[Test]
    public function can_skip_files_missing_from_disk(): void
    {
        Storage::disk(config('filepond.temp_disk', 'local'))
            ->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $serverId = $response->content();

        // Delete the actual file from disk
        Storage::disk(config('filepond.temp_disk', 'local'))
            ->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $result = $this->decodeJs(Filepond::field($serverId)->preview());

        $this->assertCount(0, $result);
    }

    #[Test]
    public function cannot_preview_with_invalid_token_in_mixed_array(): void
    {
        Storage::disk(config('filepond.temp_disk', 'local'))
            ->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $validToken = $response->content();

        $this->expectException(DecryptException::class);

        Filepond::field([$validToken, 'invalid-token', ''])->preview();
    }

    #[Test]
    public function can_restore_only_files_owned_by_authenticated_user(): void
    {
        Storage::disk(config('filepond.temp_disk', 'local'))
            ->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $response = $this
            ->actingAs($userA)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image('avatar.png', 100, 100),
            ], [
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]);

        $serverId = $response->content();

        $this->actingAs($userB);

        $result = $this->decodeJs(Filepond::field($serverId)->preview());

        $this->assertCount(0, $result);

        $this->actingAs($userA);

        $result = $this->decodeJs(Filepond::field($serverId)->preview());

        $this->assertCount(1, $result);
        $this->assertEquals(
            FilepondUtil::getFilepondId($serverId),
            FilepondUtil::getFilepondId($result[0]['source'])
        );
    }

    /**
     * Helper to decode Js::from() output back to PHP array.
     */
    private function decodeJs(Js $js): array
    {
        $string = (string) $js;

        if ($string === '[]') {
            return [];
        }

        if (preg_match("/^JSON\.parse\('(.*)'\)$/", $string, $matches)) {
            $unquoted = json_decode('"'.$matches[1].'"');

            return json_decode($unquoted, true) ?? [];
        }

        return json_decode($string, true) ?? [];
    }
}
