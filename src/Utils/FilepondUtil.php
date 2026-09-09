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
     * Convert the request to prioritize file first
     *
     * @see https://github.com/laravel/framework/issues/61356
     *
     * @return array
     */
    public static function convertRequest(Request $request)
    {
        return array_replace_recursive($request->all(), $request->allFiles());
    }

    /**
     * Get the file field name
     *
     * @return string|null
     */
    public static function getField(Request $request)
    {
        return array_key_first(Arr::dot(self::convertRequest($request)));
    }

    /**
     * Get the file from request
     *
     * @return \Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]|null
     */
    public static function getUploadedFile(Request $request)
    {
        $field = self::getField($request);

        return $request->file($field);
    }

    /**
     * Get the metadata from request
     *
     * @return array|null
     */
    public static function getMetadata(Request $request)
    {
        $field = self::getField($request);

        return json_decode($request->post($field) ?: '', true);
    }
}
