<?php

namespace App\Http\Requests\PlayerRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdatePlayerRequest extends FormRequest
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
            'event_id' => $this->route('event_id'), // Ensure team_id is always null
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
        'name' => ['sometimes', 'string', 'max:255'],

        'id_number' => [
            'sometimes',
            'string',
            Rule::unique('players', 'id_number')
                ->where(function ($query) {
                    return $query->where('id', $this->id);
                })
                ->ignore($this->id), // <-- ignore current player ID during update
        ],

        'intrams_id' => ['required', 'exists:intramural_games,id'],
        'event_id' => ['required', 'exists:events,id'],

        'id' => [
            'required',
            Rule::exists('players', 'id')->where(function ($query) {
                return $query->where('event_id', $this->event_id);
            }),
        ],
        'course_year' => ['sometimes', 'string', 'max:255'],
        'contact' => ['sometimes', 'string', 'max:255'],
        'birthdate' => ['sometimes', 'date', 'max:255'],
        'approved' => ['sometimes', 'boolean'],
        'picture' => ['nullable', 'file', 'mimes:,jpg,jpeg,png', 'max:2048'],
        'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        'parents_consent' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        'cor' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
    ];
    }
}