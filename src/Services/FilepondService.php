<?php

namespace RahulHaque\Filepond\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RahulHaque\Filepond\Models\Filepond;

class FilepondService
{
    private $client;
    private $disk;
    private $tempDisk;
    private $tempFolder;
    private $keyInAWS;
    private $bucketName;

    public function __construct()
    {
        $this->disk = config('filepond.disk', 'public');
        $this->tempDisk = config('filepond.temp_disk', 'local');
        $this->tempFolder = config('filepond.temp_folder', 'filepond/temp');
    }

    /**
     * Get the file from request
     *
     * @param  Request  $request
     * @return mixed
     */
    protected function getUploadedFile(Request $request)
    {
        $input = collect($request->allFiles())->first();

        return is_array($input) ? $input[0] : $input;
    }

    /**
     * Validate the filepond file
     *
     * @param  Request  $request
     * @param  array  $rules
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator(Request $request, array $rules)
    {
        $field = array_key_first($request->all());

        return Validator::make([$field => $this->getUploadedFile($request)], [$field => $rules]);
    }

    /**
     * Store the uploaded file in the fileponds table
     *
     * @param  Request  $request
     * @return string
     */
    public function store(Request $request)
    {
        $file = $this->getUploadedFile($request);

        $filepond = Filepond::create([
            'filepath' => $file->store($this->tempFolder, $this->tempDisk),
            'filename' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mimetypes' => $file->getClientMimeType(),
            'disk' => $this->disk,
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30))
        ]);

        return Crypt::encrypt(['id' => $filepond->id]);
    }

    /**
     * Retrieve the filepond file from encrypted text
     *
     * @param  string  $content
     * @return mixed
     */
    public function retrieve(string $content)
    {
        $input = Crypt::decrypt($content);
        return Filepond::owned()
            ->where('id', $input['id'])
            ->firstOrFail();
    }

    /**
     * Initialize and make a slot for chunk upload
     *
     * @return string
     */
    public function initChunk()
    {
        $filepond = Filepond::create([
            'filepath' => '',
            'filename' => '',
            'extension' => '',
            'mimetypes' => '',
            'disk' => $this->disk,
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
            's3_parts' => [],
        ]);

        if (! $this->isTempDiskS3()) {
            Storage::disk($this->tempDisk)->makeDirectory($this->tempFolder.'/'.$filepond->id);
        }

        return Crypt::encrypt(['id' => $filepond->id]);
    }

    /**
     * Merge chunks
     *
     * @param  Request  $request
     * @return string
     * @throws \Throwable
     */
    public function chunk(Request $request)
    {
        if ($this->isTempDiskS3()) {
            return $this->handleMultipartUploadForS3($request);
        }

        $id = Crypt::decrypt($request->patch)['id'];

        $dir = Storage::disk($this->tempDisk)->path($this->tempFolder.'/'.$id.'/');

        $filename = $request->header('upload-name');
        $length = $request->header('upload-length');
        $offset = $request->header('upload-offset');


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
                'expires_at' => now()->addMinutes(config('filepond.expiration', 30))
            ]);
        }

        return $size;
    }

    /**
     * Get the offset of the last uploaded chunk for resume
     *
     * @param string $content
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
     * Delete the filepond file and record respecting soft delete
     *
     * @param Filepond $filepond
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

    private function isTempDiskS3(): bool
    {
        if (Storage::disk($this->tempDisk)->getConfig()['driver'] === 's3') {
            $this->client = Storage::disk($this->tempDisk)->getClient();

            return true;
        }


        return false;
    }

    private function startMultiPartUploadForS3(Filepond $filepond)
    {
        $result = $this->client->createMultipartUpload([
            'Bucket'      => $this->bucketName,
            'Key'         => $this->keyInAWS,
            'ContentType' => $filepond->mimetypes,
        ]);

        $filepond->update([
            's3_multipart_upload_id' => $result['UploadId'],
        ]);
    }

    private function completeMultiPartUploadForS3(Request $request, Filepond $filepond)
    {
        $storage = Storage::disk($this->tempDisk);
        $filename = $request->header('upload-name');

        $completed = $this->client->completeMultipartUpload([
            'Bucket'          => $this->bucketName,
            'Key'             => $this->keyInAWS,
            'UploadId'        => $filepond->s3_multipart_upload_id,
            'MultipartUpload' => [
                'Parts' => $filepond->s3_parts->all(),
            ],
        ]);

        $filepond->update([
            'filepath'   => Str::afterLast($this->keyInAWS, $storage->getConfig()['root'] . DIRECTORY_SEPARATOR),
            'filename'   => $filename,
            'extension'  => pathinfo($filename, PATHINFO_EXTENSION),
            'disk'       => $this->disk,
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
        ]);
    }

    private function handleMultipartUploadForS3(Request $request)
    {
        $id = Crypt::decrypt($request->patch)['id'];

        $filepond = $this->retrieve($request->patch);
        $filename = $request->header('upload-name');
        $length = (int) $request->header('upload-length');
        $offset = (int) $request->header('upload-offset');
        $contentLength = (int) $request->header('content-length');

        $generatedFilename = md5($filepond->getKey() . $filepond->disk . $filepond->expires_at . $filepond->created_at);

        $storage = Storage::disk($this->tempDisk);
        $idPath = $this->tempFolder. DIRECTORY_SEPARATOR. $id . DIRECTORY_SEPARATOR;
        $dirWithRoot = Storage::disk($this->tempDisk)->path($idPath);

        $this->keyInAWS = $dirWithRoot . $generatedFilename . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        $this->bucketName = $storage->getConfig()['bucket'];

        if ($offset === 0) {
            $number = 1;
            $filepond->update([
                'mimetypes' => mime_content_type($request->getContent(true))
            ]);
            $this->startMultiPartUploadForS3($filepond);
        } else {
            $number = intval($offset / config('project.files.chunk_max_size_in_bytes')) + 1;
        }

        $completedPart = $this->client->uploadPart([
            'Bucket'     => $this->bucketName,
            'Key'        => $this->keyInAWS,
            'PartNumber' => $number,
            'Body'       => $request->getContent(),
            'UploadId'   => $filepond->s3_multipart_upload_id,
        ]);

        $filepond->update([
            's3_parts' => $filepond->s3_parts->push([
                'ETag' => $completedPart['ETag'],
                'PartNumber' => $number,
            ])
        ]);

        if ($isLastChunk = ($length) === ($contentLength + $offset)) {
            $this->completeMultiPartUploadForS3($request, $filepond);
        }

        return $contentLength + $offset;
    }
}
