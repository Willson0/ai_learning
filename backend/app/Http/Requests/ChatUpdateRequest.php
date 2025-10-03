<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name" => "nullable|string",
            "subjects" => "nullable|min:0|array",
            "subjects.*" => "nullable|integer|exists:subjects,id",
            "level" => "nullable|string",
            "faculty" => "nullable|string"
        ];
    }
}
