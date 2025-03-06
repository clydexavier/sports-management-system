<?php

namespace App\Http\Requests\DocumentRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Change this if you implement authorization
    }

    protected function prepareForValidation() 
    {
        $this->merge([
            'intrams_id' => $this->route('intrams_id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required','file', 'mimes:pdf,doc,docx,jpg,png', 'max:10240'], // Max file size: 10MB
            'intrams_id' => ['required', 'exists:intramural_games,id'],
        ];
    }
}
