<?php

namespace RahulHaque\Filepond\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RahulHaque\Filepond\Models\Filepond;

class FilepondService
{
    protected function getUploadedFile(Request $request)
    {
        $input = collect($request->allFiles())->first();

        return is_array($input) ? $input[0] : $input;
    }

    /**
     * Validate the filepond file
     *
     * @param  Request  $request
     * @param  array  $rules
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator(Request $request, array $rules)
    {
        $field = array_key_first($request->all());

        return Validator::make([$field => $this->getUploadedFile($request)], [$field => $rules]);
    }

    /**
     * Store the uploaded file in the fileponds table
     *
     * @param  Request  $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $file = $this->getUploadedFile($request);

        return Filepond::create([
            'filepath' => $file->store('', config('filepond.disk', 'filepond')),
            'filename' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mimetypes' => $file->getClientMimeType(),
            'disk' => config('filepond.disk', 'filepond'),
            'created_by' => auth()->id(),
            'expires_at' => now()->addMinutes(config('filepond.expiration', 30))
        ]);
    }

    /**
     * Retrieve the filepond file from encrypted text
     *
     * @param  Request  $request
     * @return mixed
     */
    public function retrieve(Request $request)
    {
        $input = Crypt::decrypt($request->getContent(), true);

        return Filepond::where('id', $input['id'])
            ->when(auth()->check(), function ($query) {
                $query->where('created_by', auth()->id());
            })
            ->first();
    }

    /**
     * Delete the filepond file and record respecting soft delete
     *
     * @param  Filepond  $filepond
     */
    public function delete(Filepond $filepond)
    {
        if (config('filepond.soft_delete', true)) {
            return $filepond->delete();
        }

        Storage::disk($filepond->disk)->delete($filepond->filepath);
        return $filepond->forceDelete();
    }
}
