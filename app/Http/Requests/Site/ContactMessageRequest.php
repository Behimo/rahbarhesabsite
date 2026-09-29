<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'phone' => 'تلفن',
            'message' => 'پیام',
        ];
    }

    public function messageAttributes(): array
    {
        $data = $this->validated();

        return [
            'name' => trim($data['first_name'].' '.$data['last_name']),
            'phone' => $data['phone'],
            'message' => $data['message'],
            'email' => '',
        ];
    }
}
