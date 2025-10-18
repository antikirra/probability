<?php

namespace Antikirra;

use InvalidArgumentException;

// Constants for probability calculations
const MAX_UINT32 = 4294967295;
const EPSILON_LOWER = 0.000001;
const EPSILON_UPPER = 0.999999;

/**
 * @param float $probability
 * @param string $key
 * @return bool
 */
function probability($probability, $key = '')
{
    // Validate probability range (type coercion handles int/float)
    if ($probability < 0.0 || $probability > 1.0) {
        throw new InvalidArgumentException('Probability must be between 0.0 and 1.0, got: ' . $probability);
    }

    // Fast path for edge cases
    if ($probability <= EPSILON_LOWER) {
        return false;
    }

    if ($probability >= EPSILON_UPPER) {
        return true;
    }

    // Use crc32 for deterministic hashing (faster than hash('crc32b'))
    // Convert to unsigned 32-bit integer using bitwise AND
    $random = $key === '' ? mt_rand(0, MAX_UINT32) : (crc32($key) & 0xFFFFFFFF);

    return $random / MAX_UINT32 <= $probability;
}
