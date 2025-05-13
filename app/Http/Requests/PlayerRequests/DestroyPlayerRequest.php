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
            //
            'intrams_id' => ['required', 'exists:intramural_games,id'],
        'event_id' => ['required', 'exists:events,id'],

        'id' => [
            'required',
            Rule::exists('players', 'id')->where(function ($query) {
                return $query->where('intrams_id', $this->intrams_id);
            }),
        ],
        ];
    }
}