<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RahulHaque\Filepond\Tests\TestCase;
use RahulHaque\Filepond\Tests\User;
use RahulHaque\Filepond\Utils\FilepondUtil;

class FilepondBladeDirectiveTest extends TestCase
{
    #[Test]
    public function can_compile_filepond_preview_directive(): void
    {
        $compiled = Blade::compileString("@filepondPreview(old('avatar'))");

        $this->assertEquals(
            "<?php echo \\RahulHaque\\Filepond\\Facades\\Filepond::field(old('avatar'))->preview(); ?>",
            $compiled
        );
    }

    #[Test]
    public function can_compile_filepond_preview_directive_with_ownership_argument(): void
    {
        $compiled = Blade::compileString("@filepondPreview(old('avatar'), false)");

        $this->assertEquals(
            "<?php echo \\RahulHaque\\Filepond\\Facades\\Filepond::field(old('avatar'), false)->preview(); ?>",
            $compiled
        );
    }

    #[Test]
    public function can_render_filepond_preview_directive(): void
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

        $this->withSession(['_old_input' => ['avatar' => $serverId]]);

        $entries = $this->decodeJsSource(Blade::render("@filepondPreview(old('avatar'))"));

        $this->assertCount(1, $entries);
        $this->assertEquals(
            FilepondUtil::getFilepondId($serverId),
            FilepondUtil::getFilepondId($entries[0]['source'])
        );
        $this->assertEquals('limbo', $entries[0]['options']['type']);
        $this->assertEquals('avatar.png', $entries[0]['options']['file']['name']);
    }

    #[Test]
    public function can_render_empty_array_when_no_old_input(): void
    {
        $rendered = Blade::render("@filepondPreview(old('avatar'))");

        $this->assertEquals('[]', trim($rendered));
    }

    /**
     * Helper to decode rendered Js output back to PHP array.
     */
    private function decodeJsSource(string $rendered): array
    {
        $rendered = trim($rendered);

        if ($rendered === '[]') {
            return [];
        }

        preg_match("/^JSON\.parse\('(.*)'\)$/", $rendered, $matches);

        return json_decode(json_decode('"'.$matches[1].'"', true), true) ?? [];
    }
}
