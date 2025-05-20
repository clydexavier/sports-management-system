<?php

namespace App\Http\Requests\EventRequests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreEventRequest extends FormRequest
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
            'hold_third_place_match' => filter_var($this->hold_third_place_match, FILTER_VALIDATE_BOOLEAN),
            'is_umbrella' => filter_var($this->is_umbrella, FILTER_VALIDATE_BOOLEAN),
            'is_team_based' => filter_var($this->is_team_based, FILTER_VALIDATE_BOOLEAN),
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
            'name' => ['required', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'type' => ['nullable', 'sometimes','string', 'max:50'],
            'gold' => ['sometimes','nullable' ,'integer', 'min:0'],
            'silver' => ['sometimes','nullable' ,'integer', 'min:0'],
            'bronze' => ['sometimes','nullable' ,'integer', 'min:0'],
            'intrams_id' => ['required', 'exists:intramural_games,id'],
            'tournament_type' => ['sometimes', 'nullable','string', 'max:50'],
            'hold_third_place_match' => ['sometimes', 'boolean'],
            'is_umbrella' => ['required', 'boolean'],
            'parent_id' => [
                'nullable',
                'exists:events,id',
                function ($attribute, $value, $fail) {
                    if ($value && $this->is_umbrella) {
                        $fail('An umbrella event cannot have a parent event.');
                    }
                }
            ],
            'is_team_based' => ['sometimes', 'boolean'],
            'venue' => ['nullable', 'string'],
            'has_independent_medaling' => ['sometimes', 'nullable','boolean'],

        ];
    }
     /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }
    
    
}