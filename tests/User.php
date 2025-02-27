<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use RahulHaque\Filepond\Traits\HasFilepond;

class User extends Authenticatable
{
    use HasFactory, HasFilepond;

    protected $guarded = [];

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
