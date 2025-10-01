<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Contracts;

use Illuminate\Http\Request;

interface UploaderInterface
{
    public function initChunkUpload(Request $request): string;

    public function handleChunk(Request $request): int;

    public function calculateOffset(Request $request): int;

    public function deleteFile(Request $request): bool;
}
