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
        return $this->hasMany(config('filepond.model'), 'created_by');
    }
}
