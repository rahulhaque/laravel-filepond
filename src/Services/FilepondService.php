<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RahulHaque\Filepond\Exceptions\InvalidChunkException;
use RahulHaque\Filepond\Models\Filepond;
use Throwable;

class FilepondService
{
    private $disk;

    private $tempDisk;

    private $tempFolder;

    private $model;

    public function __construct()
    {
        $this->disk = config('filepond.disk', 'public');
        $this->tempDisk = config('filepond.temp_disk', 'local');
        $this->tempFolder = config('filepond.temp_folder', 'filepond/temp');
        $this->model = config('filepond.model', Filepond::class);
    }

    /**
     * Validate the filepond file
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator(Request $request, array $rules)
    {
        $field = array_key_first(Arr::dot($request->all()));

        return Validator::make($request->all(), [$field => $rules]);
    }

    /**
     * Store the uploaded file in the fileponds table
     *
     * @return string
     */
    public function store(Request $request)
    {
        $file = $this->getUploadedFile($request);

        $filepond = $this->model::create([
            'filepath' => $file->store($this->tempFolder, $this->tempDisk),
            'filename' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mimetypes' => $file->getClientMimeType(),
            'disk' => $this->disk,
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
        ]);

        return Crypt::encrypt(['id' => $filepond->id]);
    }

    /**
     * Retrieve the filepond file from encrypted text
     *
     * @return mixed
     */
    public function retrieve(string $content)
    {
        $input = Crypt::decrypt($content);

        return $this->model::where('id', $input['id'])->firstOrFail();
    }

    /**
     * Initialize and make a slot for chunk upload
     *
     * @return string
     */
    public function initChunk()
    {
        $filepond = $this->model::create([
            'filepath' => '',
            'filename' => '',
            'extension' => '',
            'mimetypes' => '',
            'disk' => $this->disk,
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
        ]);

        Storage::disk($this->tempDisk)->makeDirectory($this->tempFolder.'/'.$filepond->id);

        return Crypt::encrypt(['id' => $filepond->id]);
    }

    /**
     * Merge chunks
     *
     * @return string
     *
     * @throws Throwable
     */
    public function chunk(Request $request)
    {
        $id = Crypt::decrypt($request->patch)['id'];

        $disk = Storage::disk($this->tempDisk);
        $baseDir = trim($this->tempFolder . '/' . $id, '/') . '/';

        $contentLength = (int) $request->header('Content-Length');
        $uploadLength  = (int) $request->header('Upload-Length');
        $uploadName    = $request->header('Upload-Name');
        $uploadOffset  = $request->header('Upload-Offset');

        // Ensure folder exists
        $disk->makeDirectory($baseDir);

        $chunkKey   = $baseDir . $uploadOffset;
        $chunkBytes = $request->getContent();            // raw binary
        $chunkSize  = strlen($chunkBytes);               // byte length

        // Write this chunk
        $ok = $disk->put($chunkKey, $chunkBytes, ['visibility' => 'private']);

        // Validate write success and size vs Content-Length
        if (!$ok || $chunkSize === 0 || $chunkSize !== $contentLength) {
            // Remove invalid chunk key and fail
            $disk->delete($chunkKey);
            throw new InvalidChunkException;
        }

        // Compute total size by summing sizes of numeric-named chunk files
        $size = 0;
        $chunkFiles = array_filter(
            $disk->files($baseDir),
            fn($path) => is_numeric(basename($path))
        );

        foreach ($chunkFiles as $path) {
            $size += (int) $disk->size($path);
        }

        // If all bytes are in, merge chunks into final object
        if ($size === $uploadLength) {
            $finalKey = $baseDir . $uploadName;

            // Sort chunks by numeric offset
            usort($chunkFiles, function ($a, $b) {
                return (int) basename($a) <=> (int) basename($b);
            });

            // Stream-assemble into php://temp to avoid loading into memory at once
            $tmp = fopen('php://temp', 'w+');
            if ($tmp === false) {
                throw new \RuntimeException('Unable to open temp stream.');
            }

            try {
                foreach ($chunkFiles as $path) {
                    $read = $disk->readStream($path);
                    if ($read === false) {
                        throw new \RuntimeException("Unable to read chunk: {$path}");
                    }

                    stream_copy_to_stream($read, $tmp);
                    fclose($read);
                }

                // Rewind and upload the assembled stream to the final path
                rewind($tmp);
                $disk->writeStream($finalKey, $tmp);
            } finally {
                fclose($tmp);
            }

            // Clean up chunk objects
            $disk->delete($chunkFiles);

            // Persist metadata for your FilePond record (uses your primary $this->disk)
            $filepond = $this->retrieve($request->patch);
            $filepond->update([
                'filepath'   => $this->tempFolder . '/' . $id . '/' . $uploadName,
                'filename'   => $uploadName,
                'extension'  => pathinfo($uploadName, PATHINFO_EXTENSION),
                'mimetypes'  => Storage::disk($this->tempDisk)->mimeType($finalKey),
                'disk'       => $this->disk,
                'created_by' => auth()->id(),
                'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
            ]);
        }

        return $size;
    }

    /**
     * Get the offset of the last uploaded chunk for resume
     *
     * @return false|int
     */
    public function offset(string $content)
    {
        $filepond = $this->retrieve($content);

        $dir = Storage::disk($this->tempDisk)->path($this->tempFolder.'/'.$filepond->id.'/');
        $size = 0;
        $chunks = glob($dir.'*');
        foreach ($chunks as $chunk) {
            $size += filesize($chunk);
        }

        return $size;
    }

    /**
     * Retrieve the filepond file model and content
     *
     * @return mixed
     */
    public function restore(string $content)
    {
        $filepond = $this->retrieve($content);

        return [$filepond, Storage::disk($this->tempDisk)->get($filepond->filepath)];
    }

    /**
     * Delete the filepond file and record respecting soft delete
     *
     * @return bool|null
     */
    public function delete(Filepond $filepond)
    {
        if (config('filepond.soft_delete', true)) {
            return $filepond->delete();
        }

        Storage::disk($this->tempDisk)->delete($filepond->filepath);
        Storage::disk($this->tempDisk)->deleteDirectory($this->tempFolder.'/'.$filepond->id);

        return $filepond->forceDelete();
    }

    /**
     * Get the file from request
     *
     * @return mixed
     */
    protected function getUploadedFile(Request $request)
    {
        $field = array_key_first(Arr::dot($request->all()));

        return $request->file($field);
    }
}
