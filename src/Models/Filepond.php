<?php

namespace RahulHaque\Filepond\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RahulHaque\Filepond\Interfaces\FilePondInterface;

class Filepond extends Model implements FilePondInterface
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function scopeOwned($query)
    {
        $query->when(auth()->check(), function ($query) {
            $query->where('created_by', auth()->id());
        });
    }

    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }
}
