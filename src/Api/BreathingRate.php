<?php

declare(strict_types=1);

namespace Namelivia\Fitbit\Api;

use Namelivia\Fitbit\BreathingRate\BreathingRate as BreathingRateOperations;

class BreathingRate
{
    private $breathingRate;

    public function __construct(Fitbit $fitbit)
    {
        $this->breathingRate = new BreathingRateOperations($fitbit);
    }

    public function breathingRate()
    {
        return $this->breathingRate;
    }
}
