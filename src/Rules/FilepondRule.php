<?php

namespace RahulHaque\Filepond\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use RahulHaque\Filepond\Facades\Filepond;

class FilepondRule implements Rule
{
    protected $data;
    protected $rules;
    protected $customMessages;
    protected $customAttributes;
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @param  string|array  $rules
     * @param  array  $customMessages
     * @param  array  $customAttributes
     */
    public function __construct($rules, array $customMessages = [], array $customAttributes = [])
    {
        $this->data = request()->toArray();
        $this->rules = $rules;
        $this->customMessages = $customMessages;
        $this->customAttributes = $customAttributes;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $file = Filepond::field($value)->getFile();
        
        data_set($this->data, $attribute, $file);

        $validator = Validator::make(
            $this->data,
            [$attribute => $this->rules],
            $this->customMessages,
            $this->customAttributes
        );

        $this->messages = $validator->errors()->all();

        return !$validator->fails();
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        return $this->messages;
    }
}
