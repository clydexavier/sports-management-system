<?php

namespace App\Http\Requests\PlayerRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UpdatePlayerRequest extends FormRequest
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
            'event_id' => $this->route('event_id'),
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
            'name' => ['sometimes', 'string', 'max:255'],

            'id_number' => [
                'sometimes',
                'string',
                Rule::unique('players', 'id_number')
                    ->where(function ($query) {
                        return $query->where('id', $this->id);
                    })
                    ->ignore($this->id),
            ],

            'intrams_id' => ['required', 'exists:intramural_games,id'],
            'event_id' => ['required', 'exists:events,id'],

            'id' => [
                'required',
                function ($attribute, $value, $fail) {
                    // Get the parent_id of the current event
                    $currentEventParentId = DB::table('events')
                        ->where('id', $this->event_id)
                        ->value('parent_id');
                    
                    // Check if player exists
                    $player = DB::table('players')->where('id', $value)->first();
                    
                    if (!$player) {
                        $fail('The selected player does not exist.');
                        return;
                    }
                    
                    // If player's event_id matches the requested event_id, it's valid
                    if ($player->event_id == $this->event_id) {
                        return;
                    }
                    
                    // Get the parent_id of the player's event
                    $playerEventParentId = DB::table('events')
                        ->where('id', $player->event_id)
                        ->value('parent_id');
                    
                    // If parent_ids don't match or one of them is null, fail validation
                    if ($currentEventParentId === null || $playerEventParentId === null || $currentEventParentId != $playerEventParentId) {
                        $fail('The selected player does not belong to this event or a related event.');
                    }
                },
            ],
            'course_year' => ['sometimes', 'string', 'max:255'],
            'contact' => ['sometimes', 'string', 'max:255'],
            'birthdate' => ['sometimes', 'date', 'max:255'],
            'approved' => ['sometimes', 'boolean'],
            'picture' => ['nullable', 'file', 'mimes:,jpg,jpeg,png', 'max:2048'],
            'medical_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'parents_consent' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'cor' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }
}