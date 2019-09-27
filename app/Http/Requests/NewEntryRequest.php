<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewEntryRequest extends FormRequest
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
            'placeAfter' => [
                'required',
                'regex:/^(top|[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12})$/'
            ],
            'type' => 'required|in:comment,directive,group,stanza,custom_stanza',

            'comment_value' => 'required_if:type,comment|string',

            'directive_name' => 'required_if:type,directive|string',
            'directive_value' => 'required_if:type,directive|string',
            'directive_inactive' => 'sometimes:boolean',

            'group_name' => 'required_if:type,group|string',

            'stanza_id' => 'required_if:type,stanza|not_regex:/^\-/',

            'custom_name' => 'required_if:type,custom_stanza|string',
            'custom_value' => 'required_if:type,custom_stanza|string',
            'custom_inactive' => 'sometimes:boolean',
        ];
    }

    public function messages()
    {
        return [
            'placeAfter.required' => 'The location of the new entry must be specified',
            'placeAfter.regex' => 'The format of the new entry location is invalid',
            'type.required' => 'The type of entry must be specified',
            'type.in' => 'The entry must be a Comment, Directive, Group, Stanza, or Custom Stanza',
            // ToDo: Finish the custom error messages
        ];
    }}
