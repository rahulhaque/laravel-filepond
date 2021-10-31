<?php

namespace RahulHaque\Filepond;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
                $from = Storage::disk($filepond->disk)->path($filepond->filepath);
                $to = $path.'-'.($index + 1).'.'.$filepond->extension;
                File::copy($from, $to);
                $response[] = array_merge(['id' => $filepond->id], pathinfo($to));
            }
            return $response;
        }

        $filepond = $this->getFieldModel();
        $from = Storage::disk($filepond->disk)->path($filepond->filepath);
        $to = $path.'.'.$filepond->extension;
        File::copy($from, $to);
        return array_merge(['id' => $filepond->id], pathinfo($to));
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
                $from = Storage::disk($filepond->disk)->path($filepond->filepath);
                $to = $path.'-'.($index + 1).'.'.$filepond->extension;
                File::copy($from, $to);
                $response[] = array_merge(['id' => $filepond->id], pathinfo($to));
                $this->getIsSoftDeletable() ? $filepond->delete() : $filepond->forceDelete();
            }
            return $response;
        }

        $filepond = $this->getFieldModel();
        $from = Storage::disk($filepond->disk)->path($filepond->filepath);
        $to = $path.'.'.$filepond->extension;
        File::copy($from, $to);
        $this->getIsSoftDeletable() ? $filepond->delete() : $filepond->forceDelete();
        return array_merge(['id' => $filepond->id], pathinfo($to));
    }

    /**
     * Validate a file from temporary storage
     *
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
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

        Validator::make([$field => $this->getFile()], $rules, $messages, $customAttributes)->validate();
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
                    Storage::disk($filepond->disk)->delete($filepond->filepath);
                    $filepond->forceDelete();
                }
            }
            return;
        }

        $filepond = $this->getFieldModel();
        if ($this->getIsSoftDeletable()) {
            $filepond->delete();
        } else {
            Storage::disk($filepond->disk)->delete($filepond->filepath);
            $filepond->forceDelete();
        }
    }
}
