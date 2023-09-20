<?php

namespace RahulHaque\Filepond\Traits;

trait HasFilepond {

    /**
     * User has many FilePond uploads
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fileponds()
    {
        return $this->hasMany(config('filepond.model', \RahulHaque\Filepond\Models\Filepond::class), 'created_by');
    }
}
