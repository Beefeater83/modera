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

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
class ApiExceptionListener
{
    private bool $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
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

            $response = new JsonResponse([
                'error' => $throwable->getMessage(),
                'violations' => $violations,
            ], Response::HTTP_BAD_REQUEST);

            $event->setResponse($response);

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $response = new JsonResponse([
                'error' => $throwable->getMessage(),
            ], $throwable->getStatusCode(), $throwable->getHeaders());

            $event->setResponse($response);

            return;
        }

        $data = [
            'error' => $this->debug ? $throwable->getMessage() : 'Internal Server Error',
        ];

        $event->setResponse(new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR));
    }
}
