<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * RahulHaque\Filepond\Models\Filepond
 *
 * @property int $id
 * @property string $filename
 * @property string $filepath
 * @property string $extension
 * @property string $mimetype
 * @property array $metadata
 * @property string $disk
 * @property string $upload_id
 * @property array $upload_tags
 * @property int $created_by
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Filepond extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'json',
        'upload_tags' => 'json',
        'expires_at' => 'datetime',
    ];

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
