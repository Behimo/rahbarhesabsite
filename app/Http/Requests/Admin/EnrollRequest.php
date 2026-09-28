<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EnrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'source' => ['required', 'in:manual,gift,free'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'کاربر',
            'course_id' => 'دوره',
            'source' => 'منبع',
        ];
    }
}
