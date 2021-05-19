<?php

namespace RahulHaque\Filepond\Traits;

use RahulHaque\Filepond\Models\Filepond;

trait HasFilepond {

    /**
     * User has many FilePond uploads
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fileponds()
    {
        return $this->hasMany(Filepond::class, 'created_by');
    }
}
