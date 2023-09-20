<?php

namespace RahulHaque\Filepond\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;

class FilepondModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function can_insert_to_filepond_model()
    {
        $data = [
            'filepath' => Storage::disk(config('filepond.disk', 'public'))->path('fake_filename.png'),
            'filename' => 'fake_filename.png',
            'extension' => 'png',
            'mimetypes' => 'image/png',
            'disk' => 'filepond',
            'created_by' => 1,
            'expires_at' => now()->addMinutes(30)->toISOString()
        ];

        config('filepond.model', \RahulHaque\Filepond\Models\Filepond::class)::create($data);

        $this->assertDatabaseHas('fileponds', $data);
    }

    /** @test */
    function can_update_filepond_model()
    {
        $filepond = config('filepond.model', \RahulHaque\Filepond\Models\Filepond::class)::create([
            'filepath' => Storage::disk(config('filepond.disk', 'public'))->path('fake_filename.png'),
            'filename' => 'fake_filename.png',
            'extension' => 'png',
            'mimetypes' => 'image/png',
            'disk' => 'filepond',
            'created_by' => 1,
            'expires_at' => now()->addMinutes(30)->toISOString()
        ]);

        $filename = 'new_filename.png';

        $filepond->filename = $filename;
        $filepond->save();

        $this->assertEquals(true, $filepond->filename == $filename);
    }

    /** @test */
    function can_soft_delete_filepond_model()
    {
        $filepond = config('filepond.model', \RahulHaque\Filepond\Models\Filepond::class)::create([
            'filepath' => Storage::disk(config('filepond.disk', 'public'))->path('fake_filename.png'),
            'filename' => 'fake_filename.png',
            'extension' => 'png',
            'mimetypes' => 'image/png',
            'disk' => 'filepond',
            'created_by' => 1,
            'expires_at' => now()->addMinutes(30)->toISOString()
        ]);

        $filepond->delete();

        $this->assertSoftDeleted($filepond);
    }

    /** @test */
    function can_force_delete_filepond_model()
    {
        $filepond = config('filepond.model', \RahulHaque\Filepond\Models\Filepond::class)::create([
            'filepath' => Storage::disk(config('filepond.disk', 'public'))->path('fake_filename.png'),
            'filename' => 'fake_filename.png',
            'extension' => 'png',
            'mimetypes' => 'image/png',
            'disk' => 'filepond',
            'created_by' => 1,
            'expires_at' => now()->addMinutes(30)->toISOString()
        ]);

        $filepond->forceDelete();

        $this->assertDatabaseCount('fileponds', 0);
    }
}
