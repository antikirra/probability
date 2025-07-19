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
    if (!is_float($probability)) {
        throw new InvalidArgumentException('Probability must be a float value');
    }

    if (!is_string($key)) {
        throw new InvalidArgumentException('Key must be a string value');
    }

    if ($probability < 0.0 || $probability > 1.0) {
        throw new InvalidArgumentException('Probability must be between 0.0 and 1.0, got: ' . $probability);
    }

    if ($probability === 0.0 || $probability <= 0.000001) {
        return false;
    }

    if ($probability === 1.0 || $probability >= 0.999999) {
        return true;
    }

    return ($key === '' ? mt_rand(0, 4294967295) : hash('crc32b', $key)) / 4294967295 <= $probability;
}
