<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LawEvolutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event_date' => ['required', 'string', 'max:32'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function evolutionAttributes(): array
    {
        $validated = $this->validated();

        if (! array_key_exists('sort_order', $validated) || $validated['sort_order'] === null) {
            unset($validated['sort_order']);
        }

        return $validated;
    }
}
