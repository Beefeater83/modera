<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Exception\PayloadValidationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
class ApiExceptionListener
{
    public function __construct(
        private readonly bool $debug = false,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $throwable = $event->getThrowable();

        if ($throwable instanceof PayloadValidationException) {
            $violations = [];

            foreach ($throwable->getViolations() as $violation) {
                $violations[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => (string) $violation->getMessage(),
                ];
            }

            $event->setResponse(new JsonResponse([
                'error' => $throwable->getMessage(),
                'violations' => $violations,
            ], Response::HTTP_BAD_REQUEST));

            return;
        }

        if ($throwable instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse([
                'error' => 'Access denied.',
            ], Response::HTTP_FORBIDDEN));

            return;
        }

        if ($throwable instanceof AuthenticationException) {
            $event->setResponse(new JsonResponse([
                'error' => 'Authentication required.',
            ], Response::HTTP_UNAUTHORIZED));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse([
                'error' => $throwable->getMessage(),
            ], $throwable->getStatusCode(), $throwable->getHeaders()));

            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => $this->debug
                ? $throwable->getMessage()
                : 'Internal Server Error',
        ], Response::HTTP_INTERNAL_SERVER_ERROR));
    }
}
