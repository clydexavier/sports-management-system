<?php

namespace App\Http\Requests\IntramuralRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreIntramuralGameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], // Ensure it's a string
            'location' => ['required', 'string', 'max:255'], 
            'status' => ['required', 'in:pending,in progress,completed'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'], // Ensure valid date
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'], // Ensure valid date
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