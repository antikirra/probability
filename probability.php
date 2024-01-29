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

    if ($probability === 0.0) {
        return false;
    }

    if ($probability === 1.0) {
        return true;
    }

    if ($key === '') {
        return mt_rand(0, 1e8) / 1e8 <= $probability;
    }

    return hexdec(substr(sha1($key), 0, 8)) / 4294967295 <= $probability;
}
