<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RahulHaque\Filepond\Factories\UploaderManager;
use RahulHaque\Filepond\Models\Filepond;
use RahulHaque\Filepond\Utils\FilepondUtil;
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
        $field = FilepondUtil::getField($request);

        return Validator::make($request->all(), [$field => $rules]);
    }

    /**
     * Store the uploaded file in the fileponds table
     *
     * @return string
     */
    public function store(Request $request)
    {
        $file = FilepondUtil::getUploadedFile($request);

        $metadata = FilepondUtil::getMetadata($request);

        $filepond = $this->model::create([
            'filepath' => $file->store($this->tempFolder, $this->tempDisk),
            'filename' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mimetype' => $file->getClientMimeType(),
            'metadata' => $metadata,
            'disk' => $this->disk,
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
        ]);

        return FilepondUtil::makeFilepondId(['id' => $filepond->id]);
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
     * Retrieve and stream the filepond file
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function restore(string $content)
    {
        $id = FilepondUtil::getFilepondId($content);

        $filepond = $this->model::findOrFail($id);

        if (! Storage::disk($this->tempDisk)->exists($filepond->filepath)) {
            abort(404, 'File not found on disk.');
        }

        return Storage::disk($this->tempDisk)->response(
            $filepond->filepath,
            $filepond->filename,
            [
                'Access-Control-Expose-Headers' => 'Content-Disposition, Content-Length, X-Content-Transfer-Id',
                'Content-Type' => $filepond->mimetype,
            ],
            'inline'
        );
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
}
