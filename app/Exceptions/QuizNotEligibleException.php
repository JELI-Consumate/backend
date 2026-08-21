<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * BR-05: pretest sudah pernah dikerjakan, atau posttest belum eligible
 * (journey di sektor belum seluruhnya completed).
 */
final class QuizNotEligibleException extends RuntimeException {}
