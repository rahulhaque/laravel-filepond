<?php

namespace RahulHaque\Filepond\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RahulHaque\Filepond\Models\Filepond;

class FilepondController extends Controller
{
    /**
     * FilePond ./process route logic.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function process(Request $request)
    {
        $input = collect($request->allFiles())->first();

        $uploadedFile = is_array($input) ? $input[0] : $input;

        $field = array_key_first($request->all());

        $validator = Validator::make([$field => $uploadedFile], [$field => config('filepond.validation_rules')]);

        if ($validator->fails()) {
            return Response::make($validator->errors(), 422);
        }

        $filepond = Filepond::create([
            'filepath' => $uploadedFile->store('', config('filepond.disk', 'filepond')),
            'filename' => $uploadedFile->getClientOriginalName(),
            'extension' => $uploadedFile->getClientOriginalExtension(),
            'mimetypes' => $uploadedFile->getClientMimeType(),
            'disk' => config('filepond.disk', 'filepond'),
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30))
        ]);

        $response = Crypt::encrypt(['id' => $filepond->id], true);

        return Response::make($response, 200, ['content-type' => 'text/plain']);
    }

    /**
     * FilePond ./revert route logic.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function revert(Request $request)
    {
        $input = Crypt::decrypt($request->getContent(), true);

        $filepond = Filepond::where('id', $input['id'])
            ->when(auth()->check(), function ($query) {
                $query->where('created_by', auth()->id());
            })
            ->first();

        if (config('filepond.soft_delete', true)) {
            $filepond->delete();
        } else {
            Storage::disk($filepond->disk)->delete($filepond->filepath);
            $filepond->forceDelete();
        }

        return Response::make('', 200, ['content-type' => 'text/plain']);
    }
}
