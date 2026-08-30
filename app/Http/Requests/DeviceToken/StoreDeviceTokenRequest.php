<?php

declare (strict_types=1);

namespace App\Http\Requests\DeviceToken;

use App\Enums\DevicePlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string'],
            'platform' => ['required', new Enum(DevicePlatform::class)]
        ];
    }
}