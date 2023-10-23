<?php

namespace RahulHaque\Filepond\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RahulHaque\Filepond\Models\Filepond;

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
     * Get the file from request
     *
     * @return mixed
     */
    protected function getUploadedFile(Request $request)
    {
        $field = array_key_first(Arr::dot($request->all()));

        return $request->file($field);
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
     * @throws \Throwable
     */
    public function chunk(Request $request)
    {
        $id = Crypt::decrypt($request->patch)['id'];

        $dir = Storage::disk($this->tempDisk)->path($this->tempFolder.'/'.$id.'/');

        $filename = $request->header('Upload-Name');
        $length = $request->header('Upload-Length');
        $offset = $request->header('Upload-Offset');

        file_put_contents($dir.$offset, $request->getContent());

        $size = 0;
        $chunks = glob($dir.'*');
        foreach ($chunks as $chunk) {
            $size += filesize($chunk);
        }

        if ($length == $size) {
            $file = fopen($dir.$filename, 'w');
            foreach ($chunks as $chunk) {
                $offset = basename($chunk);

                $chunkFile = fopen($chunk, 'r');
                $chunkContent = fread($chunkFile, filesize($chunk));
                fclose($chunkFile);

                fseek($file, $offset);
                fwrite($file, $chunkContent);

                unlink($chunk);
            }
            fclose($file);

            $filepond = $this->retrieve($request->patch);
            $filepond->update([
                'filepath' => $this->tempFolder.'/'.$id.'/'.$filename,
                'filename' => $filename,
                'extension' => pathinfo($filename, PATHINFO_EXTENSION),
                'mimetypes' => Storage::disk($this->tempDisk)->mimeType($this->tempFolder.'/'.$id.'/'.$filename),
                'disk' => $this->disk,
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
}
