<?php

namespace App\Http\Requests\ParticipatingTeamRequests;

use Illuminate\Foundation\Http\FormRequest;

class StorePTRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50'],
            'team_id' => ['required', 'exists:overall_teams,id'],
            'event_id' => ['required', 'exists:events,id'],
        ];
    }
}
