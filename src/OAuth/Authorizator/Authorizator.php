<?php

declare(strict_types=1);

namespace Namelivia\Fitbit\OAuth\Authorizator;

use kamermans\OAuth2\Persistence\TokenPersistenceInterface;
use Namelivia\Fitbit\OAuth\Config\Config;
use Namelivia\Fitbit\OAuth\Constants\Constants;

class Authorizator
{
    private Config $config;
    private TokenPersistenceInterface $tokenPersistence;

    public function __construct(
        Config $config,
        TokenPersistenceInterface $tokenPersistence
    ) {
        $this->config = $config;
        $this->tokenPersistence = $tokenPersistence;
    }

    public function getAuthUri()
    {
        return Constants::AUTHORIZE_URL . '?' . http_build_query([
            'client_id' => $this->config->getClientId(),
            'scope' => implode(' ', [
                'activity',
                'cardio_fitness',
                'electrocardiogram',
                'heartrate',
                'location',
                'nutrition',
                'oxygen_saturation',
                'profile',
                'respiratory_rate',
                'settings',
                'sleep',
                'social',
                'temperature',
                'weight',
            ]),
            'response_type' => 'code',
            'redirect_uri' => $this->config->getRedirectUrl(),
            'expires_in' => '604800',
        ]);
    }

    public function setAuthorizationCode(string $code)
    {
        $this->config->setCode($code);

        return $this;
    }

    public function isAuthorized()
    {
        return $this->tokenPersistence->hasToken() || $this->config->hasCode();
    }
}
