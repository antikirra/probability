<?php

namespace Antikirra;

if (!function_exists('Antikirra\probability')) {
    /**
     * @param float $probability
     * @return bool
     */
    function probability($probability)
    {
        return \Antikirra\Probability\Probability::probability($probability);
    }
}
