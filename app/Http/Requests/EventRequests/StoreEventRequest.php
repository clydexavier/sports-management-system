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
            'challonge_event_id',
            'hold_third_place_match' => filter_var($this->hold_third_place_match, FILTER_VALIDATE_BOOLEAN),

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
            'type' => ['required', 'string', 'max:50'],
            'gold' => ['required', 'integer', 'min:0'],
            'silver' => ['required', 'integer', 'min:0'],
            'bronze' => ['required', 'integer', 'min:0'],
            'intrams_id' => ['required', 'exists:intramural_games,id'],
            'status' => ['required', 'in:pending,in progress,completed'],
            'tournament_type' => ['required',  'string', 'max:50'],
            'hold_third_place_match' => ['sometimes', 'boolean'],
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