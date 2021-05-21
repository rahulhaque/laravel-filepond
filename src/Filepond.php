<?php

namespace RahulHaque\Filepond;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Filepond extends AbstractFilepond
{
    /**
     * Set the FilePond field name
     *
     * @param string|array $field
     * @return $this
     */
    public function field($field)
    {
        $this->setIsMultiple($field)
            ->setField($field)
            ->setSoftDelete(config('filepond.soft_delete', true))
            ->setFieldModel();

        return $this;
    }

    /**
     * Return file object from the field
     *
     * @return array|UploadedFile
     */
    public function getFile()
    {
        if ($this->getIsMultiple()) {
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $filepond) {
                $response[] = $this->createFileObject($filepond);
            }
            return $response;
        }

        $filepond = $this->getFieldModel();
        return $this->createFileObject($filepond);
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
     * @param string $path
     * @return array
     */
    public function copyTo(string $path)
    {
        if ($this->getIsMultiple()) {
            $i = 1;
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $filepond) {
                $from = Storage::disk($filepond->disk)->path($filepond->filepath);
                $to = $path . '-' . $i . '.' . $filepond->extension;
                File::copy($from, $to);
                $response[] = array_merge(['id' => $filepond->id], pathinfo($to));
                $i++;
            }
            return $response;
        }

        $filepond = $this->getFieldModel();
        $from = Storage::disk($filepond->disk)->path($filepond->filepath);
        $to = $path . '.' . $filepond->extension;
        File::copy($from, $to);
        return array_merge(['id' => $filepond->id], pathinfo($to));
    }

    /**
     * Copy the FilePond files to destination and delete
     *
     * @param string $path
     * @return array
     */
    public function moveTo(string $path)
    {
        if ($this->getIsMultiple()) {
            $i = 1;
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $filepond) {
                $from = Storage::disk($filepond->disk)->path($filepond->filepath);
                $to = $path . '-' . $i . '.' . $filepond->extension;
                File::copy($from, $to);
                $response[] = array_merge(['id' => $filepond->id], pathinfo($to));
                $this->getSoftDelete() ? $filepond->delete() : $filepond->forceDelete();
                $i++;
            }
            return $response;
        }

        $filepond = $this->getFieldModel();
        $from = Storage::disk($filepond->disk)->path($filepond->filepath);
        $to = $path . '.' . $filepond->extension;
        File::copy($from, $to);
        $this->getSoftDelete() ? $filepond->delete() : $filepond->forceDelete();
        return array_merge(['id' => $filepond->id], pathinfo($to));
    }

    /**
     * Delete files related to FilePond field
     *
     * @return void
     */
    public function delete()
    {
        if ($this->getIsMultiple()) {
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $filepond) {
                if ($this->getSoftDelete()) {
                    $filepond->delete();
                } else {
                    Storage::disk($filepond->disk)->delete($filepond->filepath);
                    $filepond->forceDelete();
                }
            }
        }

        $filepond = $this->getFieldModel();
        if ($this->getSoftDelete()) {
            $filepond->delete();
        } else {
            Storage::disk($filepond->disk)->delete($filepond->filepath);
            $filepond->forceDelete();
        }
    }
}
