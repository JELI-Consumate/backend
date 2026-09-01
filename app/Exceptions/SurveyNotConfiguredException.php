<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Sektor belum punya link survei (pretest/posttest) yang diisi admin --
 * tidak ada yang bisa ditandai selesai.
 */
final class SurveyNotConfiguredException extends RuntimeException {}
