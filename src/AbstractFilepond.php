<?php

declare(strict_types=1);

namespace RahulHaque\Filepond;

use const UPLOAD_ERR_OK;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Models\Filepond;

abstract class AbstractFilepond
{
    private $fieldValue;

    private $tempDisk;

    private $isMultipleUpload;

    private $fieldModel;

    private $isOwnershipAware;

    private $isSoftDeletable;

    /**
     * @return string
     */
    public function getTempDisk()
    {
        return $this->tempDisk;
    }

    /**
     * @return $this
     */
    public function setTempDisk(string $tempDisk)
    {
        $this->tempDisk = $tempDisk;

        return $this;
    }

    /**
     * Decrypt the FilePond field value data
     *
     * @return array
     */
    protected function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Set the FilePond field value data
     *
     * @return $this
     */
    protected function setFieldValue(string|array|null $fieldValue)
    {
        if (! $fieldValue) {
            $this->fieldValue = null;

            return $this;
        }

        $this->isMultipleUpload = is_array($fieldValue);

        if ($this->getIsMultipleUpload()) {
            if (! $fieldValue[0]) {
                $this->fieldValue = null;

                return $this;
            }

            $this->fieldValue = array_map(function ($input) {
                return $this->decrypt($input);
            }, $fieldValue);

            return $this;
        }

        $this->fieldValue = $this->decrypt($fieldValue);

        return $this;
    }

    /**
     * @return bool
     */
    protected function getIsMultipleUpload()
    {
        return $this->isMultipleUpload;
    }

    /**
     * Get the filepond database model for the FilePond field
     *
     * @return mixed
     */
    protected function getFieldModel()
    {
        return $this->fieldModel;
    }

    /**
     * Set the FilePond model from the field
     *
     * @return $this
     */
    protected function setFieldModel(string $model)
    {
        if (! $this->getFieldValue()) {
            $this->fieldModel = null;

            return $this;
        }

        if ($this->getIsMultipleUpload()) {
            $this->fieldModel = $model::when($this->isOwnershipAware, function ($query) {
                $query->owned();
            })
                ->whereIn('id', (new Collection($this->getFieldValue()))->pluck('id'))
                ->get();

            return $this;
        }

        $input = $this->getFieldValue();
        $this->fieldModel = $model::when($this->isOwnershipAware, function ($query) {
            $query->owned();
        })
            ->where('id', $input['id'])
            ->first();

        return $this;
    }

    /**
     * Get the soft delete from filepond config
     *
     * @return bool
     */
    protected function getIsSoftDeletable()
    {
        return $this->isSoftDeletable;
    }

    /**
     * Set the soft delete value from filepond config
     *
     * @return $this
     */
    protected function setIsSoftDeletable(bool $isSoftDeletable)
    {
        $this->isSoftDeletable = $isSoftDeletable;

        return $this;
    }

    /**
     * Get the ownership check value for filepond model
     *
     * @return bool
     */
    protected function getIsOwnershipAware()
    {
        return $this->isOwnershipAware;
    }

    /**
     * Set the ownership check value for filepond model
     *
     * @return $this
     */
    protected function setIsOwnershipAware(bool $isOwnershipAware)
    {
        $this->isOwnershipAware = $isOwnershipAware;

        return $this;
    }

    /**
     * Decrypt the FilePond field value data
     *
     * @return mixed
     */
    protected function decrypt(string $data)
    {
        return Crypt::decrypt($data, true);
    }

    /**
     * Create file object from filepond model
     *
     * @return UploadedFile
     */
    protected function createFileObject(Filepond $filepond)
    {
        $disk = Storage::disk($this->tempDisk);
        $remotePath = $filepond->filepath;

        // 1) Open a read stream from Storage (works for S3/local/etc.)
        $read = $disk->readStream($remotePath);
        if ($read === false) {
            throw new \RuntimeException("Unable to read file from disk: {$remotePath}");
        }

        // 2) Create a real temp file and open a write stream
        $tmpPath = tempnam(sys_get_temp_dir(), 'filepond_');
        if ($tmpPath === false) {
            fclose($read);
            throw new \RuntimeException('Unable to create temp file path.');
        }
        $write = fopen($tmpPath, 'w+b');
        if ($write === false) {
            fclose($read);
            @unlink($tmpPath);
            throw new \RuntimeException('Unable to open temp file for writing.');
        }

        // 3) Stream copy (no full in-memory buffering)
        try {
            stream_copy_to_stream($read, $write);
        } finally {
            fclose($read);
            fflush($write);
            fclose($write);
        }

        // 4) Resolve mime (fallback to generic)
        $mime = $filepond->mimetypes
            ?? ($disk->mimeType($remotePath) ?: 'application/octet-stream');

        // 5) Build UploadedFile in test mode (bypasses is_uploaded_file)
        return new UploadedFile(
            $tmpPath,               // local temp path
            $filepond->filename,    // original client filename
            $mime,                  // mime type
            UPLOAD_ERR_OK,          // upload error code
            true                    // test mode
        );
    }

    /**
     * Create Data URL from filepond model
     * More at - https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs
     *
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createDataUrl(Filepond $filepond)
    {
        return 'data:' . $filepond->mimetypes . ';base64,' . base64_encode(Storage::disk($this->tempDisk)->get($filepond->filepath));
    }
}
