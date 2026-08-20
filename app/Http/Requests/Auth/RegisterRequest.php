<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\Auth\RegisterData;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'date_of_birth' => ['nullable', 'date'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function toData(): RegisterData
    {
        return new RegisterData(
            name: $this->string('name')->toString(),
            email: $this->string('email')->toString(),
            phone: $this->input('phone'),
            dateOfBirth: $this->input('date_of_birth'),
            password: $this->string('password')->toString(),
        );
    }
}
