<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Drivers;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RahulHaque\Filepond\Contracts\UploaderInterface;
use RahulHaque\Filepond\Exceptions\InvalidChunkException;
use RahulHaque\Filepond\Models\Filepond;

class S3UploadDriver implements UploaderInterface
{
    private $tempDisk;

    private $tempFolder;

    private $bucket;

    private $model;

    private S3Client $client;

    public function __construct()
    {
        $this->tempDisk = config('filepond.temp_disk', 'local');
        $this->tempFolder = config('filepond.temp_folder', 'filepond/temp');
        $this->bucket = config('filesystems.disks.'.$this->tempDisk.'.bucket');
        $this->model = config('filepond.model', Filepond::class);
        $this->client = Storage::disk($this->tempDisk)->getClient();
    }

    public function initChunkUpload(Request $request): string
    {
        $filepond = $this->model::create([
            'filepath' => '',
            'filename' => Str::uuid().'.tmp',
            'extension' => '',
            'mimetypes' => '',
            'disk' => config('filepond.disk'),
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
        ]);

        $key = $this->tempFolder.'/'.$filepond->id.'/'.$filepond->filename;

        $initChunkResponse = $this->client->createMultipartUpload([
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]);

        $filepond->update([
            'upload_id' => $initChunkResponse['UploadId'],
            'upload_tags' => [],
        ]);

        return Crypt::encrypt(['id' => $filepond->id]);
    }

    public function handleChunk(Request $request): int
    {
        $id = Crypt::decrypt($request->patch)['id'];
        $filepond = $this->model::findOrFail($id);
        $key = $this->tempFolder.'/'.$filepond->id.'/'.$filepond->filename;

        $contentLength = (int) $request->header('Content-Length');
        $uploadLength = (int) $request->header('Upload-Length');
        $uploadName = $request->header('Upload-Name');

        $partNumber = count($filepond->upload_tags) + 1;

        // Check if the uploaded file and chunk are S3 compatible
        if ($partNumber === 1 && $error = $this->checkS3Compatibility($contentLength, $uploadLength)) {
            $this->client->abortMultipartUpload([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'UploadId' => $filepond->upload_id,
            ]);

            throw new InvalidChunkException($error);
        }

        try {
            $uploadPartResponse = $this->client->uploadPart([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'UploadId' => $filepond->upload_id,
                'PartNumber' => $partNumber,
                'ContentLength' => $contentLength,
                'Body' => $request->getContent(),
            ]);
        } catch (S3Exception $e) {
            throw new InvalidChunkException('Failed to upload chunk to S3.');
        }

        $uploadTags = $filepond->upload_tags;

        $uploadTags[] = [
            'PartNumber' => $partNumber,
            'ETag' => $uploadPartResponse['ETag'],
            'Size' => $contentLength,
        ];

        $filepond->update([
            'upload_tags' => $uploadTags,
        ]);

        $size = array_sum(array_column($filepond->upload_tags, 'Size'));

        if ($size === $uploadLength) {
            $tags = array_map(
                fn ($tag): array => [
                    'ETag' => $tag['ETag'],
                    'PartNumber' => $tag['PartNumber'],
                ],
                $filepond->upload_tags
            );

            try {
                $this->client->completeMultipartUpload([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                    'UploadId' => $filepond->upload_id,
                    'MultipartUpload' => ['Parts' => $tags],
                ]);
            } catch (S3Exception $e) {
                $this->client->abortMultipartUpload([
                    'Bucket' => $this->bucket,
                    'Key' => $key,
                    'UploadId' => $filepond->upload_id,
                ]);

                throw $e;
            }

            $filepond->update([
                'filepath' => $key,
                'filename' => $uploadName,
                'extension' => pathinfo($uploadName, PATHINFO_EXTENSION),
                'upload_id' => null,
                'upload_tags' => null,
                'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
            ]);
        }

        return $size;
    }

    public function calculateOffset(Request $request): int
    {
        $id = Crypt::decrypt($request->patch)['id'];
        $filepond = $this->model::findOrFail($id);

        return array_sum(array_column($filepond->upload_tags ?? [], 'Size'));
    }

    public function deleteFile(Request $request): bool
    {
        $id = Crypt::decrypt($request->getContent())['id'];

        $filepond = $this->model::findOrFail($id);

        if (config('filepond.soft_delete', true)) {
            return $filepond->delete();
        }

        Storage::disk($this->tempDisk)->delete($filepond->filepath);
        Storage::disk($this->tempDisk)->deleteDirectory($this->tempFolder.'/'.$filepond->id);

        return $filepond->forceDelete();
    }

    /**
     * Checks the chunk size and file size for S3 compatibility.
     * Returns string with reason why upload is incompatible.
     * Returns false when the upload is compatible with S3.
     */
    protected function checkS3Compatibility(int $chunkSize, int $fileSize): bool|string
    {
        $minChunkSize = 5 * 1024 * 1024; // 5 MiB
        $maxChunkSize = 5 * 1024 * 1024 * 1024; // 5 GiB
        $maxFileSize = 5 * 1024 * 1024 * 1024 * 1024; // 5 TiB
        $maxNoOfParts = 10000;

        $fileParts = (int) ceil($fileSize / $chunkSize);

        if ($fileSize > $maxFileSize) {
            return 'File size must less than or equal to 5 TiB for S3.';
        }

        if ($fileParts > $maxNoOfParts) {
            return 'Number of chunks must be less than or equal to '.$maxNoOfParts.' for S3.';
        }

        if ($chunkSize < $minChunkSize) {
            return 'Chunk size must be greater than or equal to 5MB for S3.';
        }

        if ($chunkSize > $maxChunkSize) {
            return 'Chunk size must be less than or equal to 5GB for S3.';
        }

        return false;
    }
}
