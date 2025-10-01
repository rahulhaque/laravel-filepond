<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Drivers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Contracts\UploaderInterface;
use RahulHaque\Filepond\Exceptions\InvalidChunkException;

class LocalUploadDriver implements UploaderInterface
{
    public function initChunkUpload(Request $request): string
    {
        $filepond = config('filepond.model')::create([
            'filepath' => '',
            'filename' => '',
            'extension' => '',
            'mimetypes' => '',
            'disk' => config('filepond.disk'),
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
        ]);

        Storage::disk(config('filepond.temp_disk'))->makeDirectory(config('filepond.temp_folder').DIRECTORY_SEPARATOR.$filepond->id);

        return Crypt::encrypt(['id' => $filepond->id]);
    }

    public function handleChunk(Request $request): int
    {
        $id = Crypt::decrypt($request->patch)['id'];
        $disk = Storage::disk(config('filepond.temp_disk'));
        $dir = $disk->path(config('filepond.temp_folder').DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR);

        $contentLength = (int) $request->header('Content-Length');
        $uploadLength = (int) $request->header('Upload-Length');
        $uploadName = $request->header('Upload-Name');
        $uploadOffset = (int) $request->header('Upload-Offset');

        $chunkSize = file_put_contents($dir.$uploadOffset, $request->getContent());

        if ($chunkSize === false || $chunkSize === 0 || $contentLength !== $chunkSize) {
            unlink($dir.$uploadOffset); // Remove invalid chunk to retry
            throw new InvalidChunkException;
        }

        $size = 0;
        $chunks = glob($dir.'*');
        foreach ($chunks as $chunk) {
            $size += filesize($chunk);
        }

        if ($uploadLength === $size) {
            $file = fopen($dir.$uploadName, 'w');
            foreach ($chunks as $chunk) {
                $uploadOffset = (int) basename($chunk);

                $chunkFile = fopen($chunk, 'r');
                $chunkContent = fread($chunkFile, filesize($chunk));
                fclose($chunkFile);

                fseek($file, $uploadOffset);
                fwrite($file, $chunkContent);

                unlink($chunk);
            }
            fclose($file);

            $filepond = config('filepond.model')::findOrFail($id);

            $filepond->update([
                'filepath' => config('filepond.temp_folder').DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.$uploadName,
                'filename' => $uploadName,
                'extension' => pathinfo($uploadName, PATHINFO_EXTENSION),
                'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
            ]);
        }

        return $size;
    }

    public function calculateOffset(Request $request): int
    {
        $id = Crypt::decrypt($request->patch)['id'];
        $filepond = config('filepond.model')::findOrFail($id);
        $dir = Storage::disk(config('filepond.temp_disk'))->path(config('filepond.temp_folder').DIRECTORY_SEPARATOR.$filepond->id.DIRECTORY_SEPARATOR);

        $size = 0;
        $chunks = glob($dir.'*');
        foreach ($chunks as $chunk) {
            $size += filesize($chunk);
        }

        return $size;
    }

    public function deleteFile(Request $request): bool
    {
        $id = Crypt::decrypt($request->getContent())['id'];

        $filepond = config('filepond.model')::findOrFail($id);

        if (config('filepond.soft_delete', true)) {
            return $filepond->delete();
        }

        Storage::disk(config('filepond.temp_disk'))->delete($filepond->filepath);
        Storage::disk(config('filepond.temp_disk'))->deleteDirectory(config('filepond.temp_folder').DIRECTORY_SEPARATOR.$filepond->id);

        return $filepond->forceDelete();
    }
}
