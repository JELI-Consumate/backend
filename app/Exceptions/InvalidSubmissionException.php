<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Attempt yang sudah completed_at != null bersifat immutable,
 * submit ulang harus ditolak (409).
 */
final class InvalidSubmissionException extends RuntimeException {}
