<?php


namespace RahulHaque\Filepond;


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
    private $isSoftDeletable;

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
     * @param  string|array  $fieldValue
     * @return $this
     */
    protected function setFieldValue($fieldValue)
    {
        if (!$fieldValue) {
            $this->fieldValue = null;
            return $this;
        }

        $this->isMultipleUpload = is_array($fieldValue);

        if ($this->getIsMultipleUpload()) {
            if (!$fieldValue[0]) {
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
     * @return string
     */
    public function getTempDisk()
    {
        return $this->tempDisk;
    }

    /**
     * @param  string  $tempDisk
     */
    public function setTempDisk(string $tempDisk)
    {
        $this->tempDisk = $tempDisk;
        return $this;
    }

    /**
     * @return boolean
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
    protected function setFieldModel()
    {
        if (!$this->getFieldValue()) {
            $this->fieldModel = null;
            return $this;
        }

        if ($this->getIsMultipleUpload()) {
            $this->fieldModel = Filepond::owned()
                ->whereIn('id', (new Collection($this->getFieldValue()))->pluck('id'))
                ->get();
            return $this;
        }

        $input = $this->getFieldValue();
        $this->fieldModel = Filepond::owned()
            ->where('id', $input['id'])
            ->first();
        return $this;
    }

    /**
     * Get the soft delete from filepond config
     *
     * @return boolean
     */
    protected function getIsSoftDeletable()
    {
        return $this->isSoftDeletable;
    }

    /**
     * Set the soft delete value from filepond config
     *
     * @param  bool  $isSoftDeletable
     * @return $this
     */
    protected function setIsSoftDeletable(bool $isSoftDeletable)
    {
        $this->isSoftDeletable = $isSoftDeletable;
        return $this;
    }

    /**
     * Decrypt the FilePond field value data
     *
     * @param  string  $data
     * @return mixed
     */
    protected function decrypt(string $data)
    {
        return Crypt::decrypt($data, true);
    }

    /**
     * Create file object from filepond model
     *
     * @param  Filepond  $filepond
     * @return UploadedFile
     */
    protected function createFileObject(Filepond $filepond)
    {
        return new UploadedFile(
            Storage::disk($this->tempDisk)->path($filepond->filepath),
            $filepond->filename,
            $filepond->mimetypes,
            \UPLOAD_ERR_OK,
            true
        );
    }
}
