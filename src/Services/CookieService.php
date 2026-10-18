<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\HttpFoundation\Cookie;

class CookieService
{
    public function __construct(private string $environment)
    {
    }
    public function createAccessCookie(string $accessToken): Cookie
    {
        return Cookie::create('access_token', $accessToken, new \DateTimeImmutable('+5 minutes'))
            ->withHttpOnly(true)
            ->withSecure($this->environment === 'prod')
            ->withPath('/');
    }

    public function createRefreshCookie(string $refreshTokenValue): Cookie
    {
        return Cookie::create('refresh_token', $refreshTokenValue, new \DateTimeImmutable('+1 hours'))
            ->withHttpOnly(true)
            ->withSecure($this->environment === 'prod')
            ->withPath('/');
    }
}
