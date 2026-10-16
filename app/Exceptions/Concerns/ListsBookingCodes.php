<?php

declare(strict_types=1);

namespace App\Exceptions\Concerns;

trait ListsBookingCodes
{
    private const MAX_LISTED_CODES = 5;

    /**
     * "PMJ-202610-0001, PMJ-202610-0002 dan 3 lainnya"
     *
     * @param  list<string>  $codes
     */
    private static function listCodes(array $codes): string
    {
        $listed = implode(', ', array_slice($codes, 0, self::MAX_LISTED_CODES));
        $remaining = count($codes) - self::MAX_LISTED_CODES;

        return $remaining > 0 ? "{$listed} dan {$remaining} lainnya" : $listed;
    }
}
