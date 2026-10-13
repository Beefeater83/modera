<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contract;
use App\Model\Filter;
use App\Model\Page;
use App\Model\Sort;
use App\Repository\ContractRepository;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/contracts')]
class ContractController extends AbstractApiController
{
    private ContractRepository $contractRepository;
    private InvoiceRepository $invoiceRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ContractRepository $contractRepository,
        InvoiceRepository $invoiceRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        parent::__construct($validator);
        $this->contractRepository = $contractRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('', methods: ['GET'])]
    public function list(Filter $filter, Sort $sort, Page $page): JsonResponse
    {
        $result = $this->contractRepository->getPaginated($filter, $sort, $page);

        return $this->json($result);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $contract = $this->contractRepository->getOrFail($id);

        return $this->json($contract);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = $this->getJsonPayload($request);

        $requiredFields = ['customer', 'number', 'issued', 'amountEur', 'subject'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                throw new BadRequestHttpException(sprintf('Field "%s" is required.', $field));
            }
        }

        $contract = new Contract();
        $contract->setCustomer((string) $payload['customer']);
        $contract->setNumber((string) $payload['number']);
        $contract->setIssued($this->parseDate((string) $payload['issued'], 'issued', false));
        $contract->setAmountEur((string) $payload['amountEur']);
        $contract->setSubject((string) $payload['subject']);

        if (array_key_exists('signed', $payload)) {
            $contract->setSigned($this->parseDate($payload['signed'] !== null ? (string) $payload['signed'] : null, 'signed', true));
        }

        if (array_key_exists('licencePriceEur', $payload)) {
            $contract->setLicencePriceEur($payload['licencePriceEur'] !== null ? (string) $payload['licencePriceEur'] : null);
        }

        if (array_key_exists('licenceSince', $payload)) {
            $contract->setLicenceSince($this->parseDate($payload['licenceSince'] !== null ? (string) $payload['licenceSince'] : null, 'licenceSince', true));
        }

        if (array_key_exists('note', $payload)) {
            $contract->setNote($payload['note'] !== null ? (string) $payload['note'] : null);
        }

        $this->validateEntity($contract);

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        return $this->json($contract, Response::HTTP_CREATED);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $contract = $this->contractRepository->getOrFail($id);
        $payload = $this->getJsonPayload($request);

        if (array_key_exists('customer', $payload)) {
            if ($payload['customer'] === null) {
                throw new BadRequestHttpException('Field "customer" cannot be null.');
            }
            $contract->setCustomer((string) $payload['customer']);
        }

        if (array_key_exists('number', $payload)) {
            if ($payload['number'] === null) {
                throw new BadRequestHttpException('Field "number" cannot be null.');
            }
            $contract->setNumber((string) $payload['number']);
        }

        if (array_key_exists('subject', $payload)) {
            if ($payload['subject'] === null) {
                throw new BadRequestHttpException('Field "subject" cannot be null.');
            }
            $contract->setSubject((string) $payload['subject']);
        }

        if (array_key_exists('amountEur', $payload)) {
            if ($payload['amountEur'] === null) {
                throw new BadRequestHttpException('Field "amountEur" cannot be null.');
            }
            $contract->setAmountEur((string) $payload['amountEur']);
        }

        if (array_key_exists('issued', $payload)) {
            $contract->setIssued($this->parseDate($payload['issued'] !== null ? (string) $payload['issued'] : null, 'issued', false));
        }

        if (array_key_exists('signed', $payload)) {
            $contract->setSigned($this->parseDate($payload['signed'] !== null ? (string) $payload['signed'] : null, 'signed', true));
        }

        if (array_key_exists('licencePriceEur', $payload)) {
            $contract->setLicencePriceEur($payload['licencePriceEur'] !== null ? (string) $payload['licencePriceEur'] : null);
        }

        if (array_key_exists('licenceSince', $payload)) {
            $contract->setLicenceSince($this->parseDate($payload['licenceSince'] !== null ? (string) $payload['licenceSince'] : null, 'licenceSince', true));
        }

        if (array_key_exists('note', $payload)) {
            $contract->setNote($payload['note'] !== null ? (string) $payload['note'] : null);
        }

        $this->validateEntity($contract);
        $this->entityManager->flush();

        return $this->json($contract);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $contract = $this->contractRepository->getOrFail($id);

        $invoiceCount = $this->invoiceRepository->countByCriteria([
            'contract' => ['eq' => $contract->getId()],
        ]);

        if ($invoiceCount > 0) {
            throw new ConflictHttpException('Cannot delete contract with associated invoices.');
        }

        $this->entityManager->remove($contract);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
