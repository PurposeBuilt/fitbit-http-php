<?php

declare(strict_types=1);

namespace Namelivia\Fitbit\OAuth\Middleware;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use kamermans\OAuth2\Exception\AccessTokenRequestException;
use kamermans\OAuth2\Exception\ReauthorizationException;
use kamermans\OAuth2\GrantType\AuthorizationCode;
use kamermans\OAuth2\GrantType\RefreshToken;
use kamermans\OAuth2\OAuth2Middleware;

class Middleware extends OAuth2Middleware
{
    public function __construct(
        Client $reauthClient,
        array $config
    ) {
        return parent::__construct(
            new AuthorizationCode($reauthClient, $config),
            new RefreshToken($reauthClient, $config)
        );
    }

    public function setTokenPersistence($tokenPersistence)
    {
        return parent::setTokenPersistence($tokenPersistence);
    }

    /**
     * Override to add a distributed lock and diagnostic logging around token refresh.
     *
     * Without the lock, multiple concurrent jobs that find the token expired will
     * all POST to Fitbit with the same refresh_token simultaneously. Fitbit uses
     * rotating refresh tokens — only the first request succeeds; subsequent ones
     * receive a 400 "invalid_grant" response, which the parent class silently
     * swallows, then falls through to the AuthorizationCode grant. That always
     * fails in background jobs (no code is available), producing the misleading
     * "Missing parameters: code" error.
     *
     * With the lock:
     * 1. Only one process refreshes the token at a time.
     * 2. Processes that waited re-read the token from DB — if it's now fresh they
     *    use it without making a redundant refresh request.
     * 3. The BadResponseException from a failed refresh token grant is logged
     *    before being acted on, making the real cause visible in logs.
     */
    protected function requestNewAccessToken()
    {
        $userId  = Auth::id() ?? 'unknown';
        $lockKey = "fitbit_token_refresh_user_{$userId}";
        $lock    = Cache::lock($lockKey, 60);
        $acquired = false;

        try {
            // Block up to 30 s waiting for any concurrent refresh to finish.
            $lock->block(30);
            $acquired = true;

            // Re-read from DB: another process may have refreshed while we waited.
            $freshToken = $this->tokenPersistence->restoreToken(call_user_func($this->newTokenSupplier));
            if ($freshToken && !$freshToken->isExpired()) {
                Log::info("Fitbit: token already refreshed by another process for user {$userId}, using fresh DB token");
                $this->rawToken = $freshToken;
                return;
            }

            // Token still needs refreshing — try the refresh_token grant first.
            if ($this->refreshTokenGrantType && $this->rawToken && $this->rawToken->getRefreshToken()) {
                try {
                    $rawData = $this->refreshTokenGrantType->getRawData(
                        $this->clientCredentialsSigner,
                        $this->rawToken->getRefreshToken()
                    );
                    $this->rawToken = $this->tokenFactory($rawData, $this->rawToken);
                    // Save inside the lock so waiting processes see the fresh token immediately.
                    $this->tokenPersistence->saveToken($this->rawToken);
                    Log::info("Fitbit: refresh_token grant succeeded for user {$userId}");
                    return;
                } catch (BadResponseException $e) {
                    $status = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
                    $body   = $e->getResponse() ? (string) $e->getResponse()->getBody() : 'no response body';
                    Log::error("Fitbit: refresh_token grant FAILED for user {$userId} — HTTP {$status}: {$body}");
                    $this->rawToken = null;
                }
            } else {
                Log::warning(
                    "Fitbit: skipping refresh_token grant for user {$userId} — " .
                    "rawToken=" . ($this->rawToken ? 'set' : 'null') . " " .
                    "getRefreshToken=" . ($this->rawToken && $this->rawToken->getRefreshToken() ? 'set' : 'null')
                );
            }

            // Fallback: authorization_code grant — will always fail in background jobs.
            Log::warning("Fitbit: falling back to authorization_code grant for user {$userId} — this will fail without a code");

            if ($this->grantType === null) {
                throw new ReauthorizationException('You must specify a grantType class to request an access token');
            }

            try {
                $rawData        = $this->grantType->getRawData($this->clientCredentialsSigner);
                $this->rawToken = $this->tokenFactory($rawData);
            } catch (BadResponseException $e) {
                throw new AccessTokenRequestException('Unable to request a new access token: ' . $e->getMessage(), $e);
            }

        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            Log::error("Fitbit: token refresh lock timed out after 30 s for user {$userId} — falling back to unprotected refresh");
            parent::requestNewAccessToken();
        } finally {
            if ($acquired) {
                $lock->release();
            }
        }
    }
}
