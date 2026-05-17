<?php

declare(strict_types=1);

namespace Antikirra;

use InvalidArgumentException;

/**
 * Number of distinct values crc32() can yield: 2^32. The hash is divided by
 * the bucket count — not by the largest hash — so the mapping onto [0.0, 1.0)
 * stays unbiased: every bucket, 0 included, is exactly the same width.
 */
const UINT32_BUCKETS = 4294967296;

/**
 * Number of distinct values mt_rand() can yield: mt_getrandmax() + 1 = 2^31.
 * mt_getrandmax() is documented to always return 2147483647, so the divisor
 * is a constant — this keeps the random path free of any per-call lazy init.
 */
const MT_RAND_BUCKETS = 2147483648;

/**
 * Decide whether a probabilistic event should occur.
 *
 * Without a key the outcome is random (mt_rand). With a key it is
 * deterministic (crc32): the same key always yields the same result,
 * which keeps gradual rollouts monotonic — a key enabled at a lower
 * threshold stays enabled at every higher threshold.
 *
 * @param float $probability A value between 0.0 and 1.0 inclusive.
 * @param string $key Optional key for deterministic, reproducible results.
 * @return bool
 * @throws InvalidArgumentException When $probability is NAN or outside [0.0, 1.0].
 */
function probability(float $probability, string $key = ''): bool
{
    // NAN is the only value that never equals itself — cheapest possible guard.
    // It must run first: NAN fails every comparison below and would otherwise
    // slip through to the hashing path and silently return false.
    if ($probability !== $probability) {
        throw new InvalidArgumentException('Probability must be between 0.0 and 1.0, got: NAN');
    }

    // Each outer comparison does double duty — it rejects out-of-range input
    // and short-circuits the exact 0.0 / 1.0 case — so the hot path
    // (0 < p < 1) is reached after only two float comparisons.
    if ($probability <= 0.0) {
        if ($probability < 0.0) {
            throw new InvalidArgumentException('Probability must be between 0.0 and 1.0, got: ' . $probability);
        }

        return false;
    }

    if ($probability >= 1.0) {
        if ($probability > 1.0) {
            throw new InvalidArgumentException('Probability must be between 0.0 and 1.0, got: ' . $probability);
        }

        return true;
    }

    if ($key === '') {
        return mt_rand() / MT_RAND_BUCKETS < $probability;
    }

    $hash = crc32($key);

    // crc32() returns a signed int on 32-bit PHP; normalise it to unsigned.
    // On 64-bit PHP the value is always non-negative, so this branch is dead.
    // @codeCoverageIgnoreStart
    if ($hash < 0) {
        $hash += UINT32_BUCKETS;
    }
    // @codeCoverageIgnoreEnd

    return $hash / UINT32_BUCKETS < $probability;
}
