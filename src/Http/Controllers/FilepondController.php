<?php

namespace RahulHaque\Filepond\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use RahulHaque\Filepond\Services\FilepondService;

class FilepondController extends Controller
{
    /**
     * FilePond ./process route logic.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function process(Request $request, FilepondService $service)
    {
        $validator = $service->validator($request, config('filepond.validation_rules', []));

        if ($validator->fails()) {
            return Response::make($validator->errors(), 422);
        }

        $filepond = $service->store($request);

        $response = $service->encrypt(['id' => $filepond->id]);

        return Response::make($response, 200, ['content-type' => 'text/plain']);
    }

    /**
     * FilePond ./revert route logic.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function revert(Request $request, FilepondService $service)
    {
        $filepond = $service->retrieve($request);

        $service->delete($filepond);

        return Response::make('Ok', 200, ['content-type' => 'text/plain']);
    }
}
