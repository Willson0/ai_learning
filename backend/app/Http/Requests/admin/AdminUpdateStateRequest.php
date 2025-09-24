<?php

namespace App\Http\Requests\admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateStateRequest extends FormRequest
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
            "subject_id" => "nullable|integer|exists:subjects,id",
            "type" => "nullable|string|in:ege,oge,vpr",
            "title" => "nullable|string",
            "text" => "nullable|string",
            "description" => "nullable|string",
            "materials" => "nullable",
        ];
    }
}
