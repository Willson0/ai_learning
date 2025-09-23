<?php

namespace App\Http\Requests\admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateAchievementRequest extends FormRequest
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
            "image" => "nullable|file|mimes:jpeg,png,jpg,gif,svg,webp",
            "description" => "nullable|string",
            "name" => "nullable|string",
            "parameter" => "nullable|string",
            "value" => "nullable|integer",
        ];
    }
}
