<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Utils;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;

class FilepondUtil
{
    /**
     * Create encrypted filepond id
     *
     * @return string
     */
    public static function makeFilepondId(mixed $value)
    {
        return Crypt::encrypt($value);
    }

    /**
     * Get the filepond id from encrypted content
     *
     * @return mixed
     */
    public static function getFilepondId(string $content)
    {
        return Crypt::decrypt($content)['id'];
    }

    /**
     * Get the file from request
     *
     * @return \Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]|null
     */
    public static function getUploadedFile(Request $request)
    {
        $field = array_key_first(Arr::dot($request->all()));

        return $request->file($field);
    }

    /**
     * Get the file from request
     *
     * @return array|null
     */
    public static function getMetadata(Request $request)
    {
        $field = array_key_first(Arr::dot($request->all()));

        return json_decode($request->post($field) ?: '', true);
    }
}
