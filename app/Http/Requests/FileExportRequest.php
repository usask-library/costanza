<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileExportRequest extends FormRequest
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
            'files' => 'required|array',
            'files.*.regex' => "/^.*\.json$/",
            'oclc_includes' => "sometimes|boolean",
        ];
    }

    public function messages()
    {
        return [
            'files.array' => 'The list of files to export was empty',
            'files.required' => 'The list of files to export was empty',
            'files.*.regex' => 'The files must be JSON format',
            'oclc_includes.boolean' => 'The option to use OCLC Hosted Include files must be either true or false',
        ];
    }}
