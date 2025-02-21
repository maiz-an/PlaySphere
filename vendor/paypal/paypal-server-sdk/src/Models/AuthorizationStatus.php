<?php

declare(strict_types=1);

/*
 * PaypalServerSdkLib
 *
 * This file was automatically generated by APIMATIC v3.0 ( https://www.apimatic.io ).
 */

namespace PaypalServerSdkLib\Models;

use Core\Utils\CoreHelper;
use Exception;
use stdClass;

/**
 * The status for the authorized payment.
 */
class AuthorizationStatus
{
    public const CREATED = 'CREATED';

    public const CAPTURED = 'CAPTURED';

    public const DENIED = 'DENIED';

    public const PARTIALLY_CAPTURED = 'PARTIALLY_CAPTURED';

    public const VOIDED = 'VOIDED';

    public const PENDING = 'PENDING';

    private const _ALL_VALUES =
        [self::CREATED, self::CAPTURED, self::DENIED, self::PARTIALLY_CAPTURED, self::VOIDED, self::PENDING];

    /**
     * Ensures that all the given values are present in this Enum.
     *
     * @param array|stdClass|null|string $value Value or a list/map of values to be checked
     *
     * @return array|null|string Input value(s), if all are a part of this Enum
     *
     * @throws Exception Throws exception if any given value is not in this Enum
     */
    public static function checkValue($value)
    {
        $value = json_decode(json_encode($value), true); // converts stdClass into array
        if (CoreHelper::checkValueOrValuesInList($value, self::_ALL_VALUES)) {
            return $value;
        }
        throw new Exception("$value is invalid for AuthorizationStatus.");
    }
}
