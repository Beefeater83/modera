<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Services\CookieService;
use PHPUnit\Framework\TestCase;

final class CookieServiceTest extends TestCase
{
    public function testCreateAccessCookieInProduction(): void
    {
        $service = new CookieService('prod');

        $before = time();
        $cookie = $service->createAccessCookie('access-token-value');
        $after = time();

        self::assertSame('access_token', $cookie->getName());
        self::assertSame('access-token-value', $cookie->getValue());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame('/', $cookie->getPath());

        self::assertGreaterThanOrEqual(
            $before + 299,
            $cookie->getExpiresTime()
        );
        self::assertLessThanOrEqual(
            $after + 300,
            $cookie->getExpiresTime()
        );
    }

    public function testCreateAccessCookieOutsideProduction(): void
    {
        $service = new CookieService('test');

        $cookie = $service->createAccessCookie('access-token-value');

        self::assertSame('access_token', $cookie->getName());
        self::assertSame('access-token-value', $cookie->getValue());
        self::assertTrue($cookie->isHttpOnly());
        self::assertFalse($cookie->isSecure());
        self::assertSame('/', $cookie->getPath());
    }

    public function testCreateRefreshCookieInProduction(): void
    {
        $service = new CookieService('prod');

        $before = time();
        $cookie = $service->createRefreshCookie('refresh-token-value');
        $after = time();

        self::assertSame('refresh_token', $cookie->getName());
        self::assertSame('refresh-token-value', $cookie->getValue());
        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame('/', $cookie->getPath());

        self::assertGreaterThanOrEqual(
            $before + 3599,
            $cookie->getExpiresTime()
        );
        self::assertLessThanOrEqual(
            $after + 3600,
            $cookie->getExpiresTime()
        );
    }

    public function testCreateRefreshCookieOutsideProduction(): void
    {
        $service = new CookieService('test');

        $cookie = $service->createRefreshCookie('refresh-token-value');

        self::assertSame('refresh_token', $cookie->getName());
        self::assertSame('refresh-token-value', $cookie->getValue());
        self::assertTrue($cookie->isHttpOnly());
        self::assertFalse($cookie->isSecure());
        self::assertSame('/', $cookie->getPath());
    }
}
