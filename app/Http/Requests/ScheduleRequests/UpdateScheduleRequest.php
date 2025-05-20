<?php

namespace App\Http\Requests\ScheduleRequests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateScheduleRequest extends FormRequest
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
            'id' => $this->route('id')
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
            
            'intrams_id' => ['required', 'exists:intramural_games,id'],
            'event_id' => ['required', 'exists:events,id'],
            'id' => [
                'required',
                Rule::exists('schedules', 'id')->where(function ($query) {
                    return $query->where('event_id', $this->input('event_id'));
                }),
            ],
            'date' => ['sometimes', 'nullable','date'],
            'time' => ['sometimes', 'nullable'],
            'venue' => ['sometimes','nullable' ,'string', 'max:50'],
            'suggested_play_order' => ['sometimes'],

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