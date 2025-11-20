<?php

namespace App\Http\Requests\post;

use Illuminate\Foundation\Http\FormRequest;

class PostStoreRequest extends FormRequest
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
            "text" => "required|string|min:3",
            "attachment" => "nullable|file",
            "date" => "nullable|date",
            "time_repeat" => "nullable|integer|min:61",
            "end_date" => "nullable|date",
            "end_count" => "nullable|integer|min:1",
        ];
    }
}
