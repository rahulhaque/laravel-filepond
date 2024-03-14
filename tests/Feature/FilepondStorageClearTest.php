<?php

namespace RahulHaque\Filepond\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RahulHaque\Filepond\Models\Filepond;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondStorageClearTest extends TestCase
{
    #[Test]
    public function can_clear_expired_files_from_storage()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();
        // Create 5 temporary file uploads
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($user)
                ->post(route('filepond-process'), [
                    'avatar' => UploadedFile::fake()->image('avatar-'.$i.'.png', 100, 100),
                ], [
                    'Content-Type' => 'multipart/form-data',
                    'accept' => 'application/json',
                ]);
        }

        // Update expire_at time to make them ready to clean
        Filepond::query()->update([
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('filepond:clear')
            ->expectsOutput('Total expired files and folders: 5')
            ->expectsOutput('Temporary files and folders deleted.')
            ->assertExitCode(0);
    }

    #[Test]
    public function can_force_clear_all_files_from_storage()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

        $user = User::factory()->create();
        // Create 5 temporary file uploads
        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($user)
                ->post(route('filepond-process'), [
                    'avatar' => UploadedFile::fake()->image('avatar-'.$i.'.png', 100, 100),
                ], [
                    'Content-Type' => 'multipart/form-data',
                    'accept' => 'application/json',
                ]);
        }

        $this->artisan('filepond:clear --all')
            ->expectsQuestion('Are you sure?', 'yes')
            ->assertExitCode(0);
    }
}
