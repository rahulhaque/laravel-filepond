<?php

namespace RahulHaque\Filepond\Contracts;

use Illuminate\Http\Request;

interface FilepondServerInterface
{
    /**
     * FilePond ./process route logic.
     *
     * @param Request $request
     */
    public function process(Request $request);

    /**
     * FilePond ./revert route logic.
     *
     * @param Request $request
     */
    public function revert(Request $request);
}