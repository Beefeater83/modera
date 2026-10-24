<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\ApiExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use App\Exception\PayloadValidationException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class ApiExceptionListenerTest extends TestCase
{
    private function createEvent(
        string $path,
        \Throwable $exception,
    ): ExceptionEvent {
        $request = Request::create($path);

        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }

    public function testAccessDeniedReturnsForbiddenJson(): void
    {
        $listener = new ApiExceptionListener();
        $event = $this->createEvent(
            '/api/contracts',
            new AccessDeniedException(),
        );

        $listener($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(403, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Access denied.'],
            json_decode($response->getContent(), true),
        );
    }

    public function testAuthenticationExceptionReturnsUnauthorizedJson(): void
    {
        $listener = new ApiExceptionListener();
        $event = $this->createEvent(
            '/api/contracts',
            new AuthenticationException(),
        );

        $listener($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Authentication required.'],
            json_decode($response->getContent(), true),
        );
    }

    public function testHttpExceptionPreservesStatusAndHeaders(): void
    {
        $listener = new ApiExceptionListener();
        $event = $this->createEvent(
            '/api/contracts/123',
            new HttpException(
                404,
                'Contract not found.',
                null,
                ['X-Test' => 'test-value'],
            ),
        );

        $listener($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Contract not found.'],
            json_decode($response->getContent(), true),
        );
        self::assertSame(
            'test-value',
            $response->headers->get('X-Test'),
        );
    }

    public function testUnexpectedExceptionHidesMessageWhenDebugIsDisabled(): void
    {
        $listener = new ApiExceptionListener(false);
        $event = $this->createEvent(
            '/api/contracts',
            new \RuntimeException('Database connection failed.'),
        );

        $listener($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Internal Server Error'],
            json_decode($response->getContent(), true),
        );
    }

    public function testUnexpectedExceptionShowsMessageWhenDebugIsEnabled(): void
    {
        $listener = new ApiExceptionListener(true);
        $event = $this->createEvent(
            '/api/contracts',
            new \RuntimeException('Test exception message.'),
        );

        $listener($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Test exception message.'],
            json_decode($response->getContent(), true),
        );
    }

    public function testNonApiRequestIsIgnored(): void
    {
        $listener = new ApiExceptionListener();
        $event = $this->createEvent(
            '/contracts',
            new \RuntimeException('Test exception.'),
        );

        $listener($event);

        self::assertNull($event->getResponse());
    }

    public function testPayloadValidationExceptionReturnsBadRequestJson(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'This value should not be blank.',
                null,
                [],
                null,
                'number',
                null,
            ),
        ]);

        $exception = new PayloadValidationException(
            'App\Entity\Contract',
            $violations,
        );

        $listener = new ApiExceptionListener();
        $event = $this->createEvent('/api/contracts', $exception);

        $listener($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(400, $response->getStatusCode());
        self::assertSame(
            [
                'error' => 'Validation failed for App\Entity\Contract',
                'violations' => [
                    [
                        'field' => 'number',
                        'message' => 'This value should not be blank.',
                    ],
                ],
            ],
            json_decode($response->getContent(), true),
        );
    }
}
