<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Drivers;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RahulHaque\Filepond\Contracts\ChunkProcessor;
use RahulHaque\Filepond\Exceptions\InvalidChunkException;
use RahulHaque\Filepond\Models\Filepond;

class S3ChunkProcessor implements ChunkProcessor
{
    private S3Client $client;

    public function __construct()
    {
        $this->client = Storage::disk(config('filepond.temp_disk'))->getClient();
    }

    public function initChunkUpload(Request $request): string
    {
        $filepond = Filepond::create([
            'filepath' => '',
            'filename' => Str::uuid().'.tmp',
            'extension' => '',
            'mimetypes' => '',
            'disk' => config('filepond.disk'),
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30)),
        ]);

        $key = config('filepond.temp_folder').DIRECTORY_SEPARATOR.$filepond->id.DIRECTORY_SEPARATOR.$filepond->filename;

        $initChunkResponse = $this->client->createMultipartUpload([
            'Bucket' => config('filesystems.disks.s3.bucket'),
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
        $filepond = Filepond::findOrFail($id);
        $key = config('filepond.temp_folder').DIRECTORY_SEPARATOR.$filepond->id.DIRECTORY_SEPARATOR.$filepond->filename;

        $contentLength = (int) $request->header('Content-Length');
        $uploadLength = (int) $request->header('Upload-Length');
        $uploadName = $request->header('Upload-Name');

        $partNumber = count($filepond->upload_tags) + 1;

        // Check if first chunk is less than 5MB in size
        if ($partNumber === 1 && $contentLength < (5 * 1024 * 1024)) {
            $this->client->abortMultipartUpload([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $key,
                'UploadId' => $filepond->upload_id,
            ]);

            throw new InvalidChunkException('Chunk size must be greater than or equal to 5MB for S3.');
        }

        try {
            $uploadPartResponse = $this->client->uploadPart([
                'Bucket' => config('filesystems.disks.s3.bucket'),
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
                    'Bucket' => config('filesystems.disks.s3.bucket'),
                    'Key' => $key,
                    'UploadId' => $filepond->upload_id,
                    'MultipartUpload' => ['Parts' => $tags],
                ]);
            } catch (S3Exception $e) {
                $this->client->abortMultipartUpload([
                    'Bucket' => config('filesystems.disks.s3.bucket'),
                    'Key' => $key,
                    'UploadId' => $filepond->upload_id,
                ]);

                throw $e;
            }

            $filepond->update([
                'filepath' => $key,
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
        $filepond = Filepond::findOrFail($id);
        $key = config('filepond.temp_folder').DIRECTORY_SEPARATOR.$filepond->id.DIRECTORY_SEPARATOR.$filepond->filename;

        try {
            $listPartsResponse = $this->client->listParts([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key' => $key,
                'UploadId' => $filepond->upload_id,
            ]);
        } catch (S3Exception $e) {
            throw $e;
        }

        $size = array_sum(array_column($listPartsResponse['Parts'], 'Size'));

        return $size;
    }
}
