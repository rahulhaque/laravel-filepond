<?php

namespace RahulHaque\Filepond\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RahulHaque\Filepond\Models\Filepond;
use RahulHaque\Filepond\Tests\TestCase;

class FilepondModelTest extends TestCase
{
    #[Test]
    public function can_insert_to_filepond_model()
    {
        $data = [
            'filepath' => Storage::disk(config('filepond.disk', 'public'))->path('fake_filename.png'),
            'filename' => 'fake_filename.png',
            'extension' => 'png',
            'mimetypes' => 'image/png',
            'disk' => 'filepond',
            'created_by' => 1,
            'expires_at' => now()->addMinutes(30)->toISOString(),
        ];

        Filepond::create($data);

        $this->assertDatabaseHas('fileponds', $data);
    }

    #[Test]
    public function can_update_filepond_model()
    {
        $filepond = Filepond::create([
            'filepath' => Storage::disk(config('filepond.disk', 'public'))->path('fake_filename.png'),
            'filename' => 'fake_filename.png',
            'extension' => 'png',
            'mimetypes' => 'image/png',
            'disk' => 'filepond',
            'created_by' => 1,
            'expires_at' => now()->addMinutes(30)->toISOString(),
        ]);

        $filename = 'new_filename.png';

        $filepond->filename = $filename;
        $filepond->save();

        $this->assertEquals(true, $filepond->filename == $filename);
    }

    #[Test]
    public function can_soft_delete_filepond_model()
    {
        $filepond = Filepond::create([
            'filepath' => Storage::disk(config('filepond.disk', 'public'))->path('fake_filename.png'),
            'filename' => 'fake_filename.png',
            'extension' => 'png',
            'mimetypes' => 'image/png',
            'disk' => 'filepond',
            'created_by' => 1,
            'expires_at' => now()->addMinutes(30)->toISOString(),
        ]);

        $filepond->delete();

        $this->assertSoftDeleted($filepond);
    }

    #[Test]
    public function can_force_delete_filepond_model()
    {
        $filepond = Filepond::create([
            'filepath' => Storage::disk(config('filepond.disk', 'public'))->path('fake_filename.png'),
            'filename' => 'fake_filename.png',
            'extension' => 'png',
            'mimetypes' => 'image/png',
            'disk' => 'filepond',
            'created_by' => 1,
            'expires_at' => now()->addMinutes(30)->toISOString(),
        ]);

        $filepond->forceDelete();

        $this->assertDatabaseCount('fileponds', 0);
    }
}
