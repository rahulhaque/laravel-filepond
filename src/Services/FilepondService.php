<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RahulHaque\Filepond\Factories\UploaderManager;
use RahulHaque\Filepond\Models\Filepond;
use Throwable;

class FilepondService
{
    private $disk;

    private $tempDisk;

    private $tempFolder;

    private $model;

    private $uploader;

    public function __construct(UploaderManager $uploader)
    {
        $this->disk = config('filepond.disk', 'public');
        $this->tempDisk = config('filepond.temp_disk', 'local');
        $this->tempFolder = config('filepond.temp_folder', 'filepond/temp');
        $this->model = config('filepond.model', Filepond::class);
        $this->uploader = $uploader;
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
     * Initialize and make a slot for chunk upload
     *
     * @return string
     */
    public function initChunk(Request $request)
    {
        return $this->uploader->initChunkUpload($request);
    }

    /**
     * Merge chunks
     *
     * @return int
     *
     * @throws Throwable
     */
    public function chunk(Request $request)
    {
        return $this->uploader->handleChunk($request);
    }

    /**
     * Get the offset of the last uploaded chunk for resume
     *
     * @return false|int
     */
    public function offset(Request $request)
    {
        return $this->uploader->calculateOffset($request);
    }

    /**
     * Retrieve the filepond file model and content
     *
     * @return mixed
     */
    public function restore(string $content)
    {
        $id = Crypt::decrypt($content)['id'];

        $filepond = $this->model::findOrFail($id);

        return [$filepond, Storage::disk($this->tempDisk)->get($filepond->filepath)];
    }

    /**
     * Delete the filepond file and record respecting soft delete
     *
     * @return bool|null
     */
    public function delete(Request $request)
    {
        return $this->uploader->deleteFile($request);
    }

    /**
     * Get the file from request
     *
     * @return \Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]|null
     */
    protected function getUploadedFile(Request $request)
    {
        $field = array_key_first(Arr::dot($request->all()));

        return $request->file($field);
    }
}
