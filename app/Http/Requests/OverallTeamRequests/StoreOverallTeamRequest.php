<?php

namespace App\Http\Requests\OverallTeamRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOverallTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation() {
        $this->merge([
            'intrams_id' => $this->route('intrams_id'),
            'total_gold' => 0,
            'total_silver' => 0,
            'total_bronze' => 0,
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
            //
            'name' => ['required','string', 'max:50'],
            'team_logo_path' => ['sometimes', 'string', 'max:255'],
            'intrams_id' => ['required', 'exists:intramural_games,id'],
        ];
    }
}
