<?php

declare(strict_types=1);

namespace Namelivia\Fitbit\HeartRate;

use Carbon\Carbon;
use Namelivia\Fitbit\Api\Fitbit;

class HRV
{
    private $fitbit;

    public function __construct(Fitbit $fitbit)
    {
        $this->fitbit = $fitbit;
    }


    /**
     * Returns time series data in the specified range
     * for a given resource in the format requested using units in the unit system that corresponds
     * to the Accept-Language header provided.
     *
     * @param Carbon $baseDate
     * @param Carbon $endDate
     */
    public function getByDateRange(
        Carbon $baseDate,
        Carbon $endDate
    ) {
        return $this->fitbit->get(implode('/', [
            'hrv',
            'date',
            $baseDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            'all',
        ]) . '.json');
    }
}
