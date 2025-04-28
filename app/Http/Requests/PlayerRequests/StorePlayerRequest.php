<?php

namespace App\Http\Requests\PlayerRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StorePlayerRequest extends FormRequest
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
            'event_id' => $this->route('event_id'), // Ensure team_id is always null
=======
            'event_id' => $this->route('event_id'), 
>>>>>>> dd8e76a ([UPDATE] PlayerController now modified routing)
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
            'name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'unique:players,id_number'],
            'intrams_id' => ['required', 'exists:intramural_games,id'],
            'event_id' => ['required', 'exists:events,id'],
            'team_id' => [
                'required',
<<<<<<< HEAD
                'exists:overall_teams,id',
=======
                Rule::exists('overall_teams', 'id')->where(function ($query) {
                    return $query->where('intrams_id', $this->intrams_id);
                }),
>>>>>>> dd8e76a ([UPDATE] PlayerController now modified routing)
            ],
            'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'parents_consent' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'cor' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }
}