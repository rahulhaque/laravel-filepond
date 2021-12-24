<?php

namespace RahulHaque\Filepond\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Filepond extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function scopeOwned($query)
    {
        $query->when(auth()->check(), function ($query) {
            $query->where('created_by', auth()->id());
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
