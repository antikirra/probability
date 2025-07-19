<?php

namespace Antikirra;

use InvalidArgumentException;

/**
 * @param float $probability
 * @param string $key
 * @return bool
 */
function probability($probability, $key = '')
{
    if (!is_float($probability) || !is_string($key)) {
        throw new InvalidArgumentException();
    }

    if ($probability < 0.0 || $probability > 1.0) {
        throw new InvalidArgumentException();
    }

    if ($probability === 0.0 || $probability <= 0.000001) {
        return false;
    }

    if ($probability === 1.0 || $probability >= 0.999999) {
        return true;
    }

    $value = $key === '' ? mt_rand(0, 4294967295) : hash('crc32b', $key);

    return $value / 4294967295 <= $probability;
}

