<?php

namespace App\Http\Requests\IntramuralRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;



class UpdateIntramuralGameRequest extends FormRequest
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
            'id' => [
                'required',
                Rule::exists('intramural_games', 'id'),
            ],
            'name' => ['sometimes', 'string', 'max:255'], // Ensure it's a string
            'location' => ['sometimes', 'string', 'max:255'], 
            'status' => ['sometimes', 'in:pending,in progress,completed'],
            'start_date' => ['sometimes', 'date', 'date_format:Y-m-d'], // Ensure valid date
            'end_date' => ['sometimes', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
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