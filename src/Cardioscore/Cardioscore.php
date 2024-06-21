<?php

declare(strict_types=1);

namespace Namelivia\Fitbit\Cardioscore;

use Carbon\Carbon;
use Namelivia\Fitbit\Api\Fitbit;
use Namelivia\Fitbit\Body\Period;

class Cardioscore
{
    private $fitbit;

    public function __construct(Fitbit $fitbit)
    {
        $this->fitbit = $fitbit;
    }

    /**
     * Returns weight data for an specified date
     * in the format requested using units in the unit system that corresponds
     * to the Accept-Language header provided.
     *
     * @param Carbon $date
     * @param Period $period
     */
    public function getByDate(Carbon $date)
    {
        return $this->fitbit->get(implode('/', [
            'cardioscore',
            'date',
            $date->format('Y-m-d'),
        ]) . '.json');
    }


    /**
     * Returns weight data from one providen date to another providen date
     * in the format requested using units in the unit system that corresponds
     * to the Accept-Language header provided.
     *
     * @param Carbon $baseDate
     * @param Carbon $endDate
     */
    public function getByDateRange(Carbon $baseDate, Carbon $endDate)
    {
        return $this->fitbit->get(implode('/', [
            'cardioscore',
            'date',
            $baseDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ]) . '.json');
    }
}
