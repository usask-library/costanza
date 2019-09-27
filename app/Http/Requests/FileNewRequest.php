<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileNewRequest extends FormRequest
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
            'filename' => [
                'required',
                'regex:/^.*\.json$/'
            ],
            'default' => 'sometimes|boolean',
        ];
    }

    public function messages()
    {
        return [
            'filename.required' => 'A filename is required',
            'filename.regex' => 'The filename must have a .json extension',
            'default.boolean' => 'The Use default EZproxy config options should be either Yes or No',
        ];
    }}
