<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintChannelType: string
{
    case Website = 'website';
    case Whatsapp = 'whatsapp';
    case Phone = 'phone';
    case Email = 'email';
    case App = 'app';
    case Address = 'address';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Situs / Tautan',
            self::Whatsapp => 'WhatsApp',
            self::Phone => 'Telepon',
            self::Email => 'Email',
            self::App => 'Aplikasi',
            self::Address => 'Alamat',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
