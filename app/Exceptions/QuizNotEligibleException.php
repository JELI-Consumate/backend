<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Pretest sudah pernah dikerjakan, atau posttest belum eligible
 * (journey di sektor belum seluruhnya completed). Dua kondisi ini beda
 * penyebab, jadi dibedakan lewat $apiCode supaya client bisa membranch tanpa
 * parsing pesan bebas.
 */
final class QuizNotEligibleException extends RuntimeException
{
    public function __construct(string $message, public readonly string $apiCode = 'QUIZ_NOT_ELIGIBLE')
    {
        parent::__construct($message);
    }
}
