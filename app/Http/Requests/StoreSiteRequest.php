<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'base_url' => ['required', 'string', 'regex:/^https?:\/\//', 'unique:sites,base_url'],
            'description' => ['nullable', 'string', 'max:500'],
            'pages' => ['required', 'array', 'min:1', 'max:50'],
            'pages.*' => ['required', 'string', 'regex:/^\//'],
            'responsible_person_id' => ['required', 'exists:it_staffs,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'base_url.regex' => 'The base URL must start with http:// or https://.',
            'base_url.unique' => 'A site with this base URL already exists.',
            'pages.required' => 'At least one page must be defined.',
            'pages.min' => 'At least one page must be defined.',
            'pages.max' => 'A maximum of 50 pages can be defined.',
            'pages.*.regex' => 'Each page path must start with "/".',
            'pages.*.required' => 'Each page path is required.',
            'category_id.exists' => 'The selected category does not exist.',
            'responsible_person_id.exists' => 'The selected responsible person does not exist.',
        ];
    }
}
