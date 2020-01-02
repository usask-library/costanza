<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileImportRequest extends FormRequest
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
            'EZproxyFiles' => 'required|array',
            'EZproxyFiles.*' => 'file|mimes:txt',
            'allowOverwrite' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'EZproxyFiles.array' => 'The upload does not appear to be a list of files',
            'EZproxyFiles.required' => 'One or more files files are required',
            'EZproxyFiles.*.mimes' => 'The uploaded file(s) must be plain text files',
            'allowOverwrite' => 'The allow overwrite checkbox had neither a true or false value',
        ];
    }}
