<?php

declare(strict_types=1);

namespace RahulHaque\Filepond\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Validator as FilepondValidator;
use Illuminate\Validation\Validator;
use RahulHaque\Filepond\Facades\Filepond;

class FilepondRule implements ValidationRule, ValidatorAwareRule
{
    /**
     * The validator instance.
     *
     * @var Validator
     */
    protected $validator;

    protected $rules;

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
     * @return $this
     */
    public function setValidator(Validator $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $file = Filepond::field($value)->getFile();

        $data = $this->validator->getData();

        data_set($data, $attribute, $file);

        $validator = FilepondValidator::make(
            $data,
            [$attribute => $this->rules],
            $this->validator->customMessages,
            $this->validator->customAttributes
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $fail($error);
            }
        }
    }
}
