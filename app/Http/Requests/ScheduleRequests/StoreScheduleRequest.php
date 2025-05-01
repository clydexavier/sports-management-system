<?php

namespace App\Http\Requests\ScheduleRequests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreScheduleRequest extends FormRequest
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
            'match_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('schedules', 'match_id'),
            ],
            'event_id' => ['required', 'exists:events,id'],
            'intrams_id' => ['required', 'exists:intramural_games,id'],
            'challonge_event_id' => ['required', 'string', 'max:50'],
            'team_1' => ['required', 'string', 'max:50'],
            'team_2' => ['required', 'string', 'max:50'],
            'date' => ['required', 'date'],
            'time' => ['required'],
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