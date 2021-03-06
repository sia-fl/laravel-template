<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'      => 'string|between:1,40',
            'code'      => 'string|between:1,40',
            'status'    => 'array|max:4',
            'status.*'  => 'int|in:' . STATUS_JOIN,
            'detect'    => 'string',
            'parents'   => 'array',
            'parents.*' => 'int'
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
