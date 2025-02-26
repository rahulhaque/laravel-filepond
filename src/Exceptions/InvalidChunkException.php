<?php

namespace RahulHaque\Filepond\Exceptions;

class InvalidChunkException extends \Exception
{
    protected $message = 'Invalid or corrupted chunk received.';

    protected $code = 400;
}
