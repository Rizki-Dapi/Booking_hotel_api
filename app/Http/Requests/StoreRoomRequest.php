<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'room_number' => ['required', 'string', 'max:50'],
            'floor' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(['available', 'maintenance'])],
        ];
    }
}
