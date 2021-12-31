<?php

namespace RahulHaque\Filepond;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RahulHaque\Filepond\Models\Filepond as FilepondModel;

class Filepond extends AbstractFilepond
{
    /**
     * Set the FilePond field name
     *
     * @param  string|array  $field
     * @return $this
     */
    public function field($field)
    {
        $this->setFieldValue($field)
            ->setTempDisk(config('filepond.temp_disk', 'local'))
            ->setIsSoftDeletable(config('filepond.soft_delete', true))
            ->setFieldModel();

        return $this;
    }

    /**
     * Return file object from the field
     *
     * @return array|\Illuminate\Http\UploadedFile
     */
    public function getFile()
    {
        if (!$this->getFieldValue()) {
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
     * Copy the FilePond files to destination
     *
     * @param  string  $path
     * @param  string  $disk
     * @return array
     */
    public function copyTo(string $path, string $disk = '', string $visibility = '')
    {
        if (!$this->getFieldValue()) {
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
     * @param  string  $path
     * @param  string  $disk
     * @return array
     */
    public function moveTo(string $path, string $disk = '', string $visibility = '')
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $index => $filepond) {
                $to = $path.'-'.($index + 1);
                $response[] = $this->putFile($filepond, $to, $disk, $visibility);
                $this->delete();
            }
            return $response;
        }

        $filepond = $this->getFieldModel();
        $response = $this->putFile($filepond, $path, $disk, $visibility);
        $this->delete();
        return $response;
    }

    /**
     * Validate a file from temporary storage
     *
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(array $rules, array $messages = [], array $customAttributes = [])
    {
        $old = array_key_first($rules);
        $field = explode('.', $old)[0];

        if (!$this->getFieldValue() && ($old != $field)) {
            $rules[$field] = $rules[$old];
            unset($rules[$old]);
        }

        return Validator::make([$field => $this->getFile()], $rules, $messages, $customAttributes)->validate();
    }

    /**
     * Delete files related to FilePond field
     *
     * @return void
     */
    public function delete()
    {
        if (!$this->getFieldValue()) {
            return null;
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
     * @param  FilepondModel  $filepond
     * @param  string  $path
     * @return array
     */
    private function putFile(FilepondModel $filepond, string $path, string $disk, string $visibility)
    {
        $permanentDisk = $disk == '' ? $filepond->disk : $disk;

        Storage::disk($permanentDisk)->put($path.'.'.$filepond->extension, Storage::disk($this->getTempDisk())->get($filepond->filepath), $visibility);

        return [
            "id" => $filepond->id,
            "dirname" => dirname($path.'.'.$filepond->extension),
            "basename" => basename($path.'.'.$filepond->extension),
            "extension" => $filepond->extension,
            "filename" => basename($path.'.'.$filepond->extension, '.'.$filepond->extension),
            "location" => $path.'.'.$filepond->extension,
            "url" => Storage::disk($permanentDisk)->url($path.'.'.$filepond->extension)
        ];
    }
}
