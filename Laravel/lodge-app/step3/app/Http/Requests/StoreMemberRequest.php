<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rank' => ['required', 'string', 'in:' . implode(',', Member::RANKS)],
            'initiation_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
