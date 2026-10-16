<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis. Pesannya selalu Bahasa Indonesia dan aman
 * ditampilkan ke pengguna; dipetakan menjadi toast di bootstrap/app.php.
 */
abstract class BusinessRuleException extends RuntimeException {}
