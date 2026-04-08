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
     * Get the field name from request
     *
     * @return int|string|null
     */
    public static function getField(Request $request)
    {
        return array_key_first(Arr::dot($request->all()));
    }

    /**
     * Get the file from request
     *
     * @return \Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]|null
     */
    public static function getUploadedFile(Request $request)
    {
        return $request->file(self::getField($request));
    }

    /**
     * Get the file from request
     *
     * @return array|null
     */
    public static function getMetadata(Request $request)
    {
        return json_decode($request->post(self::getField($request)) ?: '', true);
    }
}
