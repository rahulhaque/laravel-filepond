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
        // Check if chunk upload
        if ($request->hasHeader('upload-length')) {
            return Response::make($service->initChunk(), 200, ['content-type' => 'text/plain']);
        }

        $validator = $service->validator($request, config('filepond.validation_rules', []));

        if ($validator->fails()) {
            return Response::make($validator->errors(), 422);
        }

        return Response::make($service->store($request), 200, ['content-type' => 'text/plain']);
    }

    /**
     * FilePond ./patch route logic.
     *
     * @param  Request  $request
     * @param  FilepondService  $service
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function patch(Request $request, FilepondService $service)
    {
        return Response::make('Ok', 200)->withHeaders(['upload-offset' => $service->chunk($request)]);
    }

    /**
     * FilePond ./head route logic.
     *
     * @param  Request  $request
     * @param  FilepondService  $service
     * @return \Illuminate\Http\Response
     * @throws \Throwable
     */
    public function head(Request $request, FilepondService $service)
    {
        return Response::make('Ok', 200)->withHeaders(['upload-offset' => $service->offset($request->patch)]);
    }

    /**
     * FilePond ./revert route logic.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function revert(Request $request, FilepondService $service)
    {
        $filepond = $service->retrieve($request->getContent());

        $service->delete($filepond);

        return Response::make('Ok', 200, ['content-type' => 'text/plain']);
    }
}
