<?php

namespace App\Http\Requests\PlayerRequests;
use Illuminate\Validation\Rule;

use Illuminate\Foundation\Http\FormRequest;

class DestroyPlayerRequest extends FormRequest
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
<<<<<<< HEAD
            'event_id' => $this->route('event_id'), // Ensure team_id is alway
=======
            'event_id' => $this->route('event_id'), // Ensure team_id is always null
>>>>>>> dd8e76a ([UPDATE] PlayerController now modified routing)
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
        'intrams_id' => ['required', 'exists:intramural_games,id'],
        'event_id' => ['required', 'exists:events,id'],

<<<<<<< HEAD
        'team_id' => [
            'required',
            'exists:overall_teams,id',
        ],

        'id' => [
            'required',
            Rule::exists('players', 'id')->where(function ($query) {
                return $query->where('team_id', $this->team_id)
                    ->where('event_id', $this->event_id)
                    ->where('intrams_id', $this->intrams_id);
=======
        'id' => [
            'required',
            Rule::exists('players', 'id')->where(function ($query) {
                return $query->where('intrams_id', $this->intrams_id);
>>>>>>> dd8e76a ([UPDATE] PlayerController now modified routing)
            }),
        ],
        ];
    }
}