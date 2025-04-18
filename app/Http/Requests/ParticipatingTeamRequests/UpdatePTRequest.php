<?php

namespace App\Http\Requests\ParticipatingTeamRequests;
use Illuminate\Validation\Rule;


use Illuminate\Foundation\Http\FormRequest;

class UpdatePTRequest extends FormRequest
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
            'event_id' => $this->route('event_id'),
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
        //
        return [
            'name' => ['sometimes', 'string', 'max:50'],
            'team_id' => ['sometimes', 'exists:overall_teams,id'],
            'event_id' => ['required', 'exists:events,id'],
            'intrams_id' => ['required', 'exists:intramural_games,id'],
            'id' => [
                'required',
                Rule::exists('participating_teams', 'id')->where(function ($query) {
                    return $query->where('event_id', $this->event_id);
                }),
            ],
            
        ];
        
    }
}
