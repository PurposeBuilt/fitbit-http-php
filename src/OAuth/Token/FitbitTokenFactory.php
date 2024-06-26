<?php

declare(strict_types=1);

namespace Namelivia\Fitbit\OAuth\Token;

class FitbitTokenFactory
{
    public function __invoke(array $data, FitbitToken $previousToken = null)
    {
        $accessToken = null;
        $refreshToken = null;
        $expiresAt = null;
        $userId = null;
        $scopes = null;

        // Read "access_token" attribute
        if (isset($data['access_token'])) {
            $accessToken = $data['access_token'];
        }

        // Read "refresh_token" attribute
        if (isset($data['refresh_token'])) {
            $refreshToken = $data['refresh_token'];
        } elseif (null !== $previousToken) {
            // When requesting a new access token with a refresh token, the
            // server may not resend a new refresh token. In that case we
            // should keep the previous refresh token as valid.
            //
            // See http://tools.ietf.org/html/rfc6749#section-6
            $refreshToken = $previousToken->getRefreshToken();
        }

        // Read the "expires_in" attribute
        $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : null;

        // Facebook unfortunately breaks the spec by using 'expires' instead of 'expires_in'
        if (!$expiresIn && isset($data['expires'])) {
            $expiresIn = (int) $data['expires'];
        }

        // Set the absolute expiration if a relative expiration was provided
        if ($expiresIn) {
            $expiresAt = time() + $expiresIn;
        }

        if (isset($data['user_id'])) {
            $userId = $data['user_id'];
        }

        if (isset($data['scope'])) {
            $scopes = $data['scope'];
        }

        return new FitbitToken($accessToken, $refreshToken, $expiresAt, $userId, $scopes);
    }
}
