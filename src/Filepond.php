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
     * @return array
     */
    public function copyTo(string $path)
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $index => $filepond) {
                $to = $path.'-'.($index + 1);
                $response[] = $this->putFile($filepond, $to);
            }
            return $response;
        }

        $filepond = $this->getFieldModel();
        return $this->putFile($filepond, $path);
    }

    /**
     * Copy the FilePond files to destination and delete
     *
     * @param  string  $path
     * @return array
     */
    public function moveTo(string $path)
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $index => $filepond) {
                $to = $path.'-'.($index + 1);
                $response[] = $this->putFile($filepond, $to);
                $this->delete();
            }
            return $response;
        }

        $filepond = $this->getFieldModel();
        $response = $this->putFile($filepond, $path);
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
                    Storage::disk(config('filepond.temp_disk', 'local'))->delete($filepond->filepath);
                    $filepond->forceDelete();
                }
            }
            return;
        }

        $filepond = $this->getFieldModel();
        if ($this->getIsSoftDeletable()) {
            $filepond->delete();
        } else {
            Storage::disk(config('filepond.temp_disk', 'local'))->delete($filepond->filepath);
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
    private function putFile(FilepondModel $filepond, string $path)
    {
        Storage::disk($filepond->disk)->put($path.'.'.$filepond->extension, Storage::disk(config('filepond.temp_disk', 'local'))->get($filepond->filepath));

        return [
            "id" => $filepond->id,
            "dirname" => dirname($path.'.'.$filepond->extension),
            "basename" => basename($path.'.'.$filepond->extension),
            "extension" => $filepond->extension,
            "filename" => basename($path.'.'.$filepond->extension, '.'.$filepond->extension),
            "location" => $path.'.'.$filepond->extension,
            "url" => Storage::disk($filepond->disk)->url($path.'.'.$filepond->extension)
        ];
    }
}
