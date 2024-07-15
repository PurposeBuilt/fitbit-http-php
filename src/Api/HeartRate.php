<?php

declare(strict_types=1);

namespace Namelivia\Fitbit\Api;

use Namelivia\Fitbit\HeartRate\HeartRate as HeartRateOperations;
use Namelivia\Fitbit\HeartRate\HRV;

class HeartRate
{
    private $heartRate;
    private $hrv;

    public function __construct(Fitbit $fitbit)
    {
        $this->heartRate = new HeartRateOperations($fitbit);
        $this->hrv = new HRV($fitbit);
    }

    public function heartRate()
    {
        return $this->heartRate;
    }

    public function hrv()
    {
        return $this->hrv;
    }
}
