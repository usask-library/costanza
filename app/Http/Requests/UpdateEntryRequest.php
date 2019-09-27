<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntryRequest extends FormRequest
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
            'type' => 'required|in:comment,directive,group,stanza,custom_stanza',

            'comment_value' => 'required_if:type,comment|string',

            'directive_name' => 'required_if:type,directive|string',
            'directive_value' => 'required_if:type,directive|string',
            'directive_inactive' => 'sometimes:boolean',

            'group_name' => 'required_if:type,group|string',
            'group_inactive' => 'sometimes:boolean',

            'stanza_id' => 'required_if:type,stanza|not_regex:/^\-/',
            'stanza_inactive' => 'sometimes:boolean',

            'custom_name' => 'required_if:type,custom_stanza|string',
            'custom_value' => 'required_if:type,custom_stanza|string',
            'custom_inactive' => 'sometimes:boolean',
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'The type of entry must be specified',
            'type.in' => 'The entry must be a Comment, Directive, Group, Stanza, or Custom Stanza',

            'comment_value.required_if' => 'A value for the comment is required',

            'directive_name.required_if' => 'The directive name is required',
            'directive_value.required_if' => 'A value for the directive is required',

            'group_name.required_if' => 'The group name is required',

            'stanza_id.required_if' => 'The unique stanza identifier is required',

            'custom_name.required_if' => 'A name for your custom stanza is required',
            'custom_value.required_if' => 'One or more directives is required',
        ];
    }}
