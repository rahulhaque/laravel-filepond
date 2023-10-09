<?php

namespace RahulHaque\Filepond\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Validator;
use RahulHaque\Filepond\Facades\Filepond;

class FilepondRule implements DataAwareRule, Rule, ValidatorAwareRule
{
    protected $validator;

    protected $data;

    protected $rules;

    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @param  string|array  $rules
     */
    public function __construct($rules)
    {
        $this->rules = $rules;
    }

    /**
     * Set the performing validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
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
            $this->validator->customMessages,
            $this->validator->customAttributes
        );

        $this->messages = $validator->errors()->all();

        return ! $validator->fails();
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
