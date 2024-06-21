<?php

declare(strict_types=1);

namespace Namelivia\Fitbit\Api;

use Namelivia\Fitbit\Cardioscore\Cardioscore as CardioscoreOperations;

class Cardioscore
{
    private $cardioscore;

    public function __construct(Fitbit $fitbit)
    {
        $this->cardioscore = new CardioscoreOperations($fitbit);
    }

    public function cardioscore()
    {
        return $this->cardioscore;
    }
}
