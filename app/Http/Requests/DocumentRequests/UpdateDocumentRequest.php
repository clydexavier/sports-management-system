<?php

namespace App\Http\Requests\DocumentRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Change this if you implement authorization
    }

    protected function prepareForValidation() 
    {
        $this->merge([
            'intrams_id' => $this->route('intrams_id'),
            'id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'intrams_id' => ['required', 'exists:intramural_games,id'],
            'id' => [
                'required',
                Rule::exists('documents', 'id')->where(function ($query) {
                    return $query->where('intrams_id', $this->intrams_id);
                }),
            ],
        ];
    }
}
