<?php

namespace App\Http\Requests\OverallTeamRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateOverallTeamRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'], 
            'team_logo_path' => ['nullable', 'image', 'mimes:jpg,png,jpeg,gif,svg',],
            'remove_logo' => ['nullable', 'in:1'],
            'type' => ['sometimes', 'string', 'max:255'],
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