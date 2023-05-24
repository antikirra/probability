<?php

namespace Antikirra\Probability;

final class Probability
{
    /**
     * @param float $probability
     * @return bool
     */
    public static function probability($probability)
    {
        if ($probability < 0 || $probability > 1) {
            throw new \InvalidArgumentException();
        }

        if ($probability === 0.0) {
            return false;
        }

        if ($probability === 1.0) {
            return true;
        }

        return mt_rand(0, 1e6) / 1e6 <= $probability;
    }
}
