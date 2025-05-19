<?php

namespace App\Http\Requests\EventRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateEventRequest extends FormRequest
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
            'id' => $this->route('id'),
            'is_umbrella' => isset($this->is_umbrella) ? filter_var($this->is_umbrella, FILTER_VALIDATE_BOOLEAN) : null,
            'is_team_based' => isset($this->is_team_based) ? filter_var($this->is_team_based, FILTER_VALIDATE_BOOLEAN) : null,
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
            'name' => ['sometimes', 'string', 'max:50'],
            'category' => ['sometimes', 'string', 'max:50'],
            'type' => ['sometimes', 'string', 'max:50'],
            'gold' => ['sometimes', 'integer', 'min:0'],
            'silver' => ['sometimes', 'integer', 'min:0'],
            'bronze' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:pending,in progress,completed'],
            'tournament_type' => ['sometimes',  'string', 'max:50'],
            'hold_third_place_match' => ['sometimes', 'boolean'],
            'intrams_id' => ['required', 'exists:intramural_games,id'],
            'id' => [
                'required',
                Rule::exists('events', 'id')->where(function ($query) {
                    return $query->where('intrams_id', $this->intrams_id);
                }),
            ],
            'is_umbrella' => ['sometimes', 'boolean'],
            'parent_id' => [
                'sometimes', 
                'nullable',
                'exists:events,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->umbrella) {
                        $fail('An umbrella event cannot have a parent event.');
                    }
                }
            ],
            'is_team_based' => ['sometimes', 'boolean'],
            'venue' => ['sometimes', 'nullable', 'string'],
            'has_independent_medaling' => ['sometimes', 'nullable','boolean'],

        ];
    }
}