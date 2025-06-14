<?php

namespace App\Http\Requests\PodiumRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePodiumRequest extends FormRequest
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
            'event_id' => $this->route('event_id'),
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
            'event_id' => [
                'required',
                //ensures that only one podium exist per event
                'unique:podiums,event_id', 
                Rule::exists('events', 'id')->where(function ($query) {
                    return $query->where('intrams_id', $this->input('intrams_id'));
                }),
            ],            
            'gold_team_id' => ['required', 'exists:overall_teams,id'],
            'silver_team_id' => ['required', 'exists:overall_teams,id'],
            'bronze_team_id' => ['required', 'exists:overall_teams,id'],
        ];
    }
}