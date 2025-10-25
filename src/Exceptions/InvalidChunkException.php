<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Exceptions;

use Exception;

class InvalidChunkException extends Exception
{
    protected $message = 'Invalid or corrupted chunk received.';

    protected $code = 400;
}
