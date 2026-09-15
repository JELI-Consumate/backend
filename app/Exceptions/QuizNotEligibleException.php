<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Pretest sudah pernah dikerjakan, atau posttest belum eligible
 * (journey di sektor belum seluruhnya completed).
 */
final class QuizNotEligibleException extends RuntimeException {}
