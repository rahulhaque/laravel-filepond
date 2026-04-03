<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Drivers;

use Illuminate\Http\Request;
use Illuminate\Http\Testing\MimeType;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Contracts\UploaderInterface;
use RahulHaque\Filepond\Exceptions\InvalidChunkException;
use RahulHaque\Filepond\Models\Filepond;
use RahulHaque\Filepond\Utils\FilepondUtil;

class LocalUploadDriver implements UploaderInterface
{
    private $tempDisk;

    private $tempFolder;

    private $model;

    public function __construct()
    {
        $this->tempDisk = config('filepond.temp_disk', 'local');
        $this->tempFolder = config('filepond.temp_folder', 'filepond/temp');
        $this->model = config('filepond.model', Filepond::class);
    }

    public function initChunkUpload(Request $request): string
    {
        $filepond = $this->model::create([
            'filepath' => '',
            'filename' => '',
            'extension' => '',
            'mimetype' => '',
            'metadata' => FilepondUtil::getMetadata($request),
            'disk' => config('filepond.disk'),
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
        ]);

        Storage::disk($this->tempDisk)->makeDirectory($this->tempFolder.DIRECTORY_SEPARATOR.$filepond->id);

        return FilepondUtil::makeFilepondId(['id' => $filepond->id]);
    }

    public function handleChunk(Request $request): int
    {
        $id = FilepondUtil::getFilepondId($request->patch);
        $dir = Storage::disk($this->tempDisk)->path($this->tempFolder.DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR);

        $contentLength = (int) $request->header('Content-Length');
        $uploadLength = (int) $request->header('Upload-Length');
        $uploadName = $request->header('Upload-Name');
        $uploadOffset = (int) $request->header('Upload-Offset');

        $file = fopen($dir.$id, 'c+b');
        fseek($file, $uploadOffset);
        $chunk = stream_copy_to_stream($request->getContent(true), $file);
        fclose($file);

        if ($chunk === false || $chunk === 0 || $chunk !== $contentLength) {
            throw new InvalidChunkException;
        }

        $size = $uploadOffset + $chunk;

        if ($size === $uploadLength) {
            rename($dir.$id, $dir.$uploadName);

            $filepond = $this->model::findOrFail($id);

            $filepond->update([
                'filepath' => $this->tempFolder.DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.$uploadName,
                'filename' => $uploadName,
                'extension' => pathinfo($uploadName, PATHINFO_EXTENSION),
                'mimetype' => MimeType::from($uploadName),
                'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
            ]);
        }

        return $size;
    }

    public function calculateOffset(Request $request): int
    {
        $id = FilepondUtil::getFilepondId($request->patch);
        $filepond = $this->model::findOrFail($id);

        $filepath = Storage::disk($this->tempDisk)->path($this->tempFolder.DIRECTORY_SEPARATOR.$filepond->id.DIRECTORY_SEPARATOR.$filepond->id);

        if (! is_file($filepath)) {
            return 0;
        }

        clearstatcache(true, $filepath);

        return filesize($filepath);
    }

    public function deleteFile(Request $request): bool
    {
        $id = FilepondUtil::getFilepondId($request->getContent());

        $filepond = $this->model::findOrFail($id);

        if (config('filepond.soft_delete', true)) {
            return $filepond->delete();
        }

        Storage::disk($this->tempDisk)->delete($filepond->filepath);
        Storage::disk($this->tempDisk)->deleteDirectory($this->tempFolder.DIRECTORY_SEPARATOR.$filepond->id);

        return $filepond->forceDelete();
    }
}
