<?php

declare(strict_types=1);

namespace Namelivia\Fitbit\OAuth\Token;

use kamermans\OAuth2\Token\RawToken;


class FitbitToken extends RawToken implements Serializable, FitbitTokenInterface
{
    use FitbitTokenSerializer;

    /**
     * ExtendedToken constructor.
     *
     * @param string $accessToken
     * @param string $refreshToken
     * @param int    $expiresAt
     * @param string $userId
     * @param string $scope
     */
    public function __construct($accessToken = null, $refreshToken = null, $expiresAt = null, $userId = null, $scopes = null)
    {
        // Call parent constructor to initialize common properties
        parent::__construct($accessToken, $refreshToken, $expiresAt);
        $this->userId = (string) $userId;
        $this->scopes = (string) $scopes;
    }

    /**
     * Gets the user ID.
     *
     * @return string|null
     */
    public function getUserId()
    {
        return $this->userId;
    }
    /**
     * Gets the approved scopes.
     *
     * @return string|null
     */
    public function getScopes()
    {
        return $this->scopes;
    }
}
