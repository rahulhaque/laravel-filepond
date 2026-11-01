<?php

namespace RahulHaque\Filepond\Utils;

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
     * Check if the file size is within the max previewable size
     *
     * @return bool
     */
    public static function isPreviewable(int $fileSize)
    {
        $previewableSize = config('filepond.max_previewable_file_size');

        return $previewableSize === null || $fileSize <= $previewableSize * 1024;
    }
}
