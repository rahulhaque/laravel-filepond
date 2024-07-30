<?php

namespace RahulHaque\Filepond\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Facades\Filepond;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondDiskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     *
     * @group disk-test
     */
    public function can_move_file_local_to_local()
    {
        $pathToMove = 'moved/avatar';

        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        Storage::disk(config('filepond.disk', 'public'))->deleteDirectory('moved');

        $user = factory(User::class)->create();

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

        Storage::disk(config('filepond.disk', 'public'))->assertExists($fileInfo['location']);
    }

    /**
     * @test
     *
     * @group disk-test
     */
    public function can_move_file_external_to_external()
    {
        Config::set('filepond.temp_disk', 's3');
        Config::set('filepond.disk', 's3');

        $pathToMove = 'moved/avatar';

        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        Storage::disk(config('filepond.disk', 'public'))->deleteDirectory('moved');

        $user = factory(User::class)->create();

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

        Storage::disk(config('filepond.disk', 'public'))->assertExists($fileInfo['location']);
    }

    /**
     * @test
     *
     * @group disk-test
     */
    public function can_move_file_local_to_external()
    {
        Config::set('filepond.disk', 's3');

        $pathToMove = 'moved/avatar';

        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        Storage::disk(config('filepond.disk', 'public'))->deleteDirectory('moved');

        $user = factory(User::class)->create();

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

        Storage::disk(config('filepond.disk', 'public'))->assertExists($fileInfo['location']);
    }

    /**
     * @test
     *
     * @group disk-test
     */
    public function can_move_file_external_to_local()
    {
        Config::set('filepond.temp_disk', 's3');

        $pathToMove = 'moved/avatar';

        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        Storage::disk(config('filepond.disk', 'public'))->deleteDirectory('moved');

        $user = factory(User::class)->create();

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

        Storage::disk(config('filepond.disk', 'public'))->assertExists($fileInfo['location']);
    }
}
