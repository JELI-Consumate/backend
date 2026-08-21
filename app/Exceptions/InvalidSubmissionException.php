<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * BR-08: attempt yang sudah completed_at != null bersifat immutable —
 * submit ulang harus ditolak (409).
 */
final class InvalidSubmissionException extends RuntimeException {}
