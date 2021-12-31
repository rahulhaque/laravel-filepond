<?php

namespace RahulHaque\Filepond\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \RahulHaque\Filepond\Filepond field(string|array $field)
 * @method static \RahulHaque\Filepond\Filepond getFile()
 * @method static \RahulHaque\Filepond\Filepond getModel()
 * @method static \RahulHaque\Filepond\Filepond copyTo(string $path, string $disk = '', string $visibility = '')
 * @method static \RahulHaque\Filepond\Filepond moveTo(string $path, string $disk = '', string $visibility = '')
 * @method static \RahulHaque\Filepond\Filepond validate(array $rules, array $messages = [], array $customAttributes = [])
 * @method static \RahulHaque\Filepond\Filepond delete()
 *
 * @see \RahulHaque\Filepond\Filepond
 */
class Filepond extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'filepond';
    }
}
