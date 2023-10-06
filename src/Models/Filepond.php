<?php

namespace RahulHaque\Filepond\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Filepond extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $table = 'fileponds';

    public function scopeOwned($query)
    {
        $query->when(auth()->check(), function ($query) {
            $query->where($this->getTable().'.created_by', auth()->id());
        });
    }

    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }
}
