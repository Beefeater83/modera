<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\PayloadValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractApiController extends AbstractController
{
    protected ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    protected function getJsonPayload(Request $request): array
    {
        $content = $request->getContent();
        if ($content === '') {
            return [];
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BadRequestHttpException('Invalid JSON payload: ' . $e->getMessage(), $e);
        }

        if (!is_array($payload)) {
            throw new BadRequestHttpException('JSON payload must be a JSON object.');
        }

        return $payload;
    }

    protected function validateEntity(object $entity): void
    {
        $violations = $this->validator->validate($entity);
        if (count($violations) > 0) {
            throw new PayloadValidationException(get_class($entity), $violations);
        }
    }

    protected function parseDate(?string $value, string $fieldName, bool $nullable = false): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            if ($nullable) {
                return null;
            }
            throw new BadRequestHttpException(sprintf('Field "%s" cannot be empty.', $fieldName));
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new BadRequestHttpException(sprintf('Field "%s" must be a valid date in format YYYY-MM-DD.', $fieldName));
        }

        return $date;
    }
}
