<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_number' => ['sometimes', 'string', 'max:50'],
            'floor' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(['available', 'maintenance'])],
        ];
    }
}
