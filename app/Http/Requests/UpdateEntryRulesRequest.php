<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntryRulesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'rules' => 'required|array',
            'rules.*.rule' => 'in:Prepend,Append,Replace',
            'rules.*.enabled' => 'boolean',
            'rules.*.term' => 'string|nullable',
            'rules.*.value' => 'string|nullable',
        ];
    }

    public function messages()
    {
        return [
            'rules.required' => 'A list of rules is required',
            'rules.array' => 'A list of rules is required',
            'rules.*.rule.in' => 'Only Prepend, Append, and Replace rules are currently allowed',
            'rules.*.enabled' => 'The enabled flag must a True/False value',
        ];
    }}
