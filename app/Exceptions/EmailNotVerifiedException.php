<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class EmailNotVerifiedException extends RuntimeException
{
    public function __construct(string $message = 'Email kamu belum diverifikasi. Silakan cek inbox atau minta kirim ulang email verifikasi.')
    {
        parent::__construct($message);
    }
}
