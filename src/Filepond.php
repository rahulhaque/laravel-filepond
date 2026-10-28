<?php

declare(strict_types=1);

namespace RahulHaque\Filepond;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
use RahulHaque\Filepond\Models\Filepond as FilepondModel;
use RahulHaque\Filepond\Utils\FilepondUtil;
use Throwable;

class Filepond extends AbstractFilepond
{
    /**
     * Set the FilePond field name
     *
     * @return $this
     */
    public function field(string|array|null $field, bool $checkOwnership = true)
    {
        $this->setFieldValue($field)
            ->setTempDisk(config('filepond.temp_disk', 'local'))
            ->setIsSoftDeletable(config('filepond.soft_delete', true))
            ->setIsOwnershipAware($checkOwnership)
            ->setFieldModel(config('filepond.model', FilepondModel::class));

        return $this;
    }

    /**
     * Return file object from the field
     *
     * @return \Illuminate\Http\UploadedFile|array<int, \Illuminate\Http\UploadedFile>|null
     */
    public function getFile()
    {
        if (! $this->getFieldValue() || ! $this->getFieldModel()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            return $this->getFieldModel()->map(function ($filepond) {
                return $this->createFileObject($filepond);
            })->toArray();
        }

        return $this->createFileObject($this->getFieldModel());
    }

    /**
     * Get the filepond database model for the FilePond field
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->getFieldModel();
    }

    /**
     * Return metadata associated with the file
     *
     * @return array|array<int, array>|null
     */
    public function getMetadata()
    {
        if (! $this->getFieldValue() || ! $this->getFieldModel()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            return $this->getFieldModel()->pluck('metadata')->toArray();
        }

        return $this->getFieldModel()->metadata;
    }

    /**
     * Convert server IDs from old form input into FilePond compatible
     * files array for restoring temporary uploads after form refresh.
     */
    public function preview(): Js
    {
        if (! $this->getFieldValue() || ! $this->getFieldModel()) {
            return Js::from([]);
        }

        $fileponds = $this->getIsMultipleUpload()
            ? $this->getFieldModel()
            : [$this->getFieldModel()];

        $files = [];
        foreach ($fileponds as $filepond) {
            // Get file size, try catch
            // to check file existence
            try {
                $fileSize = Storage::disk($this->getTempDisk())->size($filepond->filepath);
            } catch (Throwable) {
                continue;
            }

            $type = FilepondUtil::isPreviewable($fileSize) ? 'limbo' : 'local';

            $files[] = [
                'source' => FilepondUtil::makeFilepondId(['id' => $filepond->id]),
                'options' => [
                    'type' => $type,
                    'file' => [
                        'name' => $filepond->filename,
                        'size' => $fileSize,
                        'type' => $filepond->mimetype,
                    ],
                ],
            ];
        }

        return Js::from($files);
    }

    /**
     * Copy the FilePond files to destination
     *
     * @return array{
     *     id: int,
     *     dirname: string,
     *     basename: string,
     *     extension: string,
     *     mimetype: string,
     *     filename: string,
     *     location: string,
     *     url: string
     * }|array<int, array{
     *     id: int,
     *     dirname: string,
     *     basename: string,
     *     extension: string,
     *     mimetype: string,
     *     filename: string,
     *     location: string,
     *     url: string
     * }>|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function copyTo(string $path, string $disk = '', string $visibility = '')
    {
        if (! $this->getFieldValue() || ! $this->getFieldModel()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $index => $filepond) {
                $to = $path.'-'.($index + 1);
                $response[] = $this->putFile($filepond, $to, $disk, $visibility);
            }

            return $response;
        }

        $filepond = $this->getFieldModel();

        return $this->putFile($filepond, $path, $disk, $visibility);
    }

    /**
     * Copy the FilePond files to destination and delete
     *
     * @return array{
     *     id: int,
     *     dirname: string,
     *     basename: string,
     *     extension: string,
     *     mimetype: string,
     *     filename: string,
     *     location: string,
     *     url: string
     * }|array<int, array{
     *     id: int,
     *     dirname: string,
     *     basename: string,
     *     extension: string,
     *     mimetype: string,
     *     filename: string,
     *     location: string,
     *     url: string
     * }>|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function moveTo(string $path, string $disk = '', string $visibility = '')
    {
        if (! $this->getFieldValue() || ! $this->getFieldModel()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $index => $filepond) {
                $to = $path.'-'.($index + 1);
                $response[] = $this->putFile($filepond, $to, $disk, $visibility);
            }
            $this->delete();

            return $response;
        }

        $filepond = $this->getFieldModel();
        $response = $this->putFile($filepond, $path, $disk, $visibility);
        $this->delete();

        return $response;
    }

    /**
     * Delete files related to FilePond field
     *
     * @return void
     */
    public function delete()
    {
        if (! $this->getFieldValue() || ! $this->getFieldModel()) {
            return;
        }

        if ($this->getIsMultipleUpload()) {
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $filepond) {
                if ($this->getIsSoftDeletable()) {
                    $filepond->delete();
                } else {
                    Storage::disk($this->getTempDisk())->delete($filepond->filepath);
                    $filepond->forceDelete();
                }
            }

            return;
        }

        $filepond = $this->getFieldModel();
        if ($this->getIsSoftDeletable()) {
            $filepond->delete();
        } else {
            Storage::disk($this->getTempDisk())->delete($filepond->filepath);
            $filepond->forceDelete();
        }
    }

    /**
     * Put the file in permanent storage and return response
     *
     * @return array{
     *     id: int,
     *     dirname: string,
     *     basename: string,
     *     extension: string,
     *     mimetype: string,
     *     filename: string,
     *     location: string,
     *     url: string
     * }
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function putFile(FilepondModel $filepond, string $path, string $disk, string $visibility)
    {
        $permanentDisk = $disk === '' ? $filepond->disk : $disk;

        $pathInfo = pathinfo($path);

        Storage::disk($permanentDisk)->writeStream(
            $pathInfo['dirname'].DIRECTORY_SEPARATOR.$pathInfo['filename'].'.'.$filepond->extension,
            Storage::disk($this->getTempDisk())->readStream($filepond->filepath),
            $visibility !== '' ? ['visibility' => $visibility] : []
        );

        return [
            'id' => $filepond->id,
            'dirname' => dirname($path.'.'.$filepond->extension),
            'basename' => basename($path.'.'.$filepond->extension),
            'extension' => $filepond->extension,
            'mimetype' => $filepond->mimetype,
            'filename' => basename($path.'.'.$filepond->extension, '.'.$filepond->extension),
            'location' => $path.'.'.$filepond->extension,
            'url' => Storage::disk($permanentDisk)->url($path.'.'.$filepond->extension),
        ];
    }
}
