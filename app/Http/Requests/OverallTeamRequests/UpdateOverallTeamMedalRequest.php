<?php

namespace App\Http\Requests\OverallTeamRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOverallTeamMedalRequest extends FormRequest
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
            'id' => $this->route('id'),
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
            'total_gold' => ['required', 'integer', 'min:0'],
            'total_silver' => ['required', 'integer', 'min:0'],
            'total_bronze' => ['required' , 'integer', 'min:0'],
            'intrams_id' => ['required', 'exists:intramural_games,id'],
            'id' => [
                'required',
                Rule::exists('overall_teams', 'id')->where(function ($query) {
                    return $query->where('intrams_id', $this->intrams_id);
                }),
            ],
        ];
    }
}
