<?php

namespace RahulHaque\Filepond\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondProcessRouteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function can_validate_filepond_file_upload_request()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

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
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));

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

        $fileById = config('filepond.model', \RahulHaque\Filepond\Models\Filepond::class)::find($data['id']);

        Storage::disk(config('filepond.temp_disk', 'local'))->assertExists($fileById->filepath);
    }


    /** @test */
    function can_assing_creator_to_model_and_has_correct_relationship_mapping()
    {
        Storage::disk(config('filepond.temp_disk', 'local'))->deleteDirectory(config('filepond.temp_folder', 'filepond/temp'));
        $user = User::factory()->create();
	    $fileName = 'avatar.png';

	    $response = $this
            ->actingAs($user)
            ->post(route('filepond-process'), [
                'avatar' => UploadedFile::fake()->image($fileName, 100, 100)
            ], [
                'Content-Type' => 'multipart/form-data',
                'accept' => 'application/json'
            ]);

        $data = Crypt::decrypt($response->content(), true);
        $filepondModel = config('filepond.model', \RahulHaque\Filepond\Models\Filepond::class)::find($data['id']);

		$this->assertEquals($user->id, $filepondModel->created_by);
		$this->assertEquals(1, $user->fileponds->count());
		$this->assertEquals($fileName, $user->fileponds->first()->filename);
    }



}
