<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

use Illuminate\Support\Facades\Validator;

class TransformValidator
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     * @return array{passes: bool, errors: array<string, mixed>}
     */
    public function validate(array $data, array $rules): array
    {
        if ($rules === []) {
            return [
                'passes' => true,
                'errors' => [],
            ];
        }

        $validator = Validator::make($data, $rules);

        return [
            'passes' => ! $validator->fails(),
            'errors' => $validator->errors()->toArray(),
        ];
    }
}
