<?php


namespace RahulHaque\Filepond;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RahulHaque\Filepond\Models\Filepond as FilepondModel;

abstract class AbstractFilepond
{
    private $field;
    private $isMultiple;
    private $fieldModel;
    private $softDelete;

    /**
     * Decrypt the FilePond field data
     *
     * @return array
     */
    protected function getField()
    {
        return $this->field;
    }

    /**
     * Set the FilePond field data
     *
     * @param  string|array  $field
     * @return $this
     */
    protected function setField($field)
    {
        if (!$field) {
            $this->field = null;
            return $this;
        }

        if ($this->getIsMultiple()) {
            $this->field = array_map(function ($input) {
                return $this->decrypt($input);
            }, $field);
            return $this;
        }

        $this->field = $this->decrypt($field);
        return $this;
    }

    /**
     * @return boolean
     */
    protected function getIsMultiple()
    {
        return $this->isMultiple;
    }

    /**
     * Set if the upload type is multiple
     *
     * @param  string|array  $field
     * @return $this
     */
    protected function setIsMultiple($field)
    {
        $this->isMultiple = is_array($field);
        return $this;
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
        if (!$this->getField()) {
            $this->fieldModel = null;
            return $this;
        }

        if ($this->getIsMultiple()) {
            $input = new Collection($this->getField());
            $fileponds = FilepondModel::whereIn('id', $input->pluck('id'))
                ->when(auth()->check(), function ($query) {
                    $query->where('created_by', auth()->id());
                })
                ->get();

            $this->fieldModel = $fileponds->count() > 0 ? $fileponds : null;
            return $this;
        }

        $input = $this->getField();
        $this->fieldModel = FilepondModel::where('id', $input['id'])
            ->when(auth()->check(), function ($query) {
                $query->where('created_by', auth()->id());
            })
            ->first();
        return $this;
    }

    /**
     * Get the soft delete from filepond config
     *
     * @return boolean
     */
    protected function getSoftDelete()
    {
        return $this->softDelete;
    }

    /**
     * Set the soft delete value from filepond config
     *
     * @param  bool  $softDelete
     * @return $this
     */
    protected function setSoftDelete(bool $softDelete)
    {
        $this->softDelete = $softDelete;
        return $this;
    }

    /**
     * Decrypt the FilePond field data
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
     * @param  FilepondModel  $filepond
     * @return UploadedFile
     */
    protected function createFileObject(FilepondModel $filepond)
    {
        return new UploadedFile(
            Storage::disk($filepond->disk)->path($filepond->filepath),
            $filepond->filename,
            $filepond->mimetypes,
            \UPLOAD_ERR_OK,
            true
        );
    }


}