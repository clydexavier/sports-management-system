<?php

namespace App\Http\Requests\PlayerRequests;

use Illuminate\Foundation\Http\FormRequest;

class TeamsVarsityPlayerRequest extends FormRequest
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
        $this->merge([
            'intrams_id' => $this->route('intrams_id'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'intrams_id' => ['required', 'exists:intramural_games,id'],
        ];
    }
}