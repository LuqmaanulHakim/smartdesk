<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => ['required', 'email', 'max:255'],

            'subject' => ['required', 'string', 'max:255'],

            'message' => ['required', 'string', 'min:20', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.min' => 'Your message must contain at least 20 characters.',
            'message.max' => 'Your message cannot exceed 5,000 characters.',
        ];
    }
}