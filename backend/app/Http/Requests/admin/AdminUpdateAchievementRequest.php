<?php

namespace App\Http\Requests;

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
            "progress" => "nullable|string",
            "title" => "nullable|string",
            "type" => "nullable|string|in:lessons,channel,tournament",
        ];
    }
}
