<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:200'],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
