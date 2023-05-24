<?php

if (!function_exists('probability')) {
    /**
     * @param float $probability
     * @return bool
     */
    function probability($probability)
    {
        return \Antikirra\Probability\Probability::probability($probability);
    }
}
