<?php

namespace App\Http\Requests\UserRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        // Here you can implement authorization logic
        // For example, only admin users can update roles
        return true; // Change according to your authorization policy
    }


    protected function prepareForValidation() {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'role' => ['required', Rule::in(['admin', 'GAM', 'tsecretary', 'secretariat', 'user', 'scheduler'])],
        ];

        // Add conditional validation based on the role
        if ($this->input('role') === 'GAM') {
            $rules['intrams_id'] = 'required|exists:intramural_games,id';
            $rules['team_id'] = 'required|exists:overall_teams,id';
        } elseif ($this->input('role') === 'tsecretary') {
            $rules['intrams_id'] = 'required|exists:intramural_games,id';
            $rules['event_id'] = 'required|exists:events,id';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'role.required' => 'The role is required.',
            'role.in' => 'The selected role is invalid.',
            'intrams_id.required' => 'The intramural assignment is required for this role.',
            'intrams_id.exists' => 'The selected intramural does not exist.',
            'team_id.required' => 'The team assignment is required for GAM role.',
            'team_id.exists' => 'The selected team does not exist.',
            'event_id.required' => 'The event assignment is required for Tournament Secretary role.',
            'event_id.exists' => 'The selected event does not exist.',
        ];
    }
}