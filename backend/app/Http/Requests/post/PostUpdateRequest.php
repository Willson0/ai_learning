<?php

namespace App\Http\Requests\post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use function PHPUnit\Framework\isJson;

class PostUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        Log::critical($this);
        Log::critical(json_decode($this["end_date"]));
        if ($this->has("time_repeat"))
            if (json_decode($this["time_repeat"]) === null) $this["time_repeat"] = null;
        if ($this->has("end_date"))
            if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(T(\d{2}):(\d{2}):(\d{2})(\.\d+)?(Z|([+-])(\d{2}):(\d{2}))?)?$/', $this["end_date"])) $this["end_date"] = null;
        if ($this->has("end_count"))
            if (json_decode($this["end_count"]) === null) $this["end_count"] = null;
        Log::critical($this);
//        $this->merge([
//            'time_repeat' => (json_decode($this->input('time_repeat')) && () ? $this->input('time_repeat') : null,
//            'end_date' => (json_decode($this->input('end_date')) && ($this->has("end_date"))) ? $this->input('end_date') : null,
//            'end_count' => (json_decode($this->input('end_count')) && ($this->has("end_count"))) ? $this->input('end_count') : null,
//        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "text" => "nullable|string|min:3",
            "date" => "nullable|date",
            "time_repeat" => "nullable|integer|min:61",
            "end_date" => "nullable|date",
            "end_count" => "nullable|integer|min:1",
        ];
    }
}
