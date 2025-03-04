<?php

namespace App\Http\Requests\IntramuralRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'name' => ['sometimes', 'string', 'max:255'],
            'year' => ['sometimes', 'integer', 'min:2000', 'digits:4'],
            'id' => [
                'required',
                Rule::exists('intramural_games', 'id'),
            ],
        ];
    }
}
