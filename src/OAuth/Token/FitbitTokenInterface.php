<?php

namespace Namelivia\Fitbit\OAuth\Token;

use kamermans\OAuth2\Token\TokenInterface;

interface FitbitTokenInterface extends TokenInterface
{
    /**
     * @return string The access token
     */
    public function getUserId();

}
