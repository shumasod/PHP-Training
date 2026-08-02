<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLodgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'founded_year' => ['required', 'integer', 'min:1717', 'max:' . date('Y')],
        ];
    }
    
    public function messages(): array
    {
        return [
            'founded_year.min' => 'The founded year must be at least 1717 (the year of the first Grand Lodge).',
            'founded_year.max' => 'The founded year cannot be in the future.'
        ];
    }
}
