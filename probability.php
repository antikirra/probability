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

    if ($key === '') {
        return mt_rand(0, 1e6) / 1e6 <= $probability;
    }

    return crc32($key) / 4294967295 <= $probability;
}
