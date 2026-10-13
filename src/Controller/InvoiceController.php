<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Invoice;
use App\Model\Filter;
use App\Model\Page;
use App\Model\Sort;
use App\Repository\ContractRepository;
use App\Repository\InvoiceRepository;
use App\Service\InvoiceCalculationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/invoices')]
class InvoiceController extends AbstractApiController
{
    private InvoiceRepository $invoiceRepository;
    private ContractRepository $contractRepository;
    private InvoiceCalculationService $calculationService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        InvoiceRepository $invoiceRepository,
        ContractRepository $contractRepository,
        InvoiceCalculationService $calculationService,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        parent::__construct($validator);
        $this->invoiceRepository = $invoiceRepository;
        $this->contractRepository = $contractRepository;
        $this->calculationService = $calculationService;
        $this->entityManager = $entityManager;
    }

    #[Route('', methods: ['GET'])]
    public function list(Filter $filter, Sort $sort, Page $page): JsonResponse
    {
        $result = $this->invoiceRepository->getPaginated($filter, $sort, $page);

        return $this->json($result);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $invoice = $this->invoiceRepository->getOrFail($id);

        return $this->json($invoice);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = $this->getJsonPayload($request);

        $requiredFields = ['number', 'issued', 'priceEur', 'quantity', 'amountUahExclVat', 'nomenclature'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                throw new BadRequestHttpException(sprintf('Field "%s" is required.', $field));
            }
        }

        $rawContract = $payload['contract'] ?? $payload['contractId'] ?? null;
        if ($rawContract === null || $rawContract === '') {
            throw new BadRequestHttpException('Field "contract" is required.');
        }

        $contractId = is_array($rawContract) ? ($rawContract['id'] ?? null) : $rawContract;
        if (!is_numeric($contractId)) {
            throw new BadRequestHttpException('Field "contract" must reference a valid contract ID.');
        }

        $contract = $this->contractRepository->getOrFail((int) $contractId);

        $invoice = new Invoice();
        $invoice->setContract($contract);
        $invoice->setNumber((string) $payload['number']);
        $invoice->setIssued($this->parseDate((string) $payload['issued'], 'issued', false));
        $invoice->setPriceEur((string) $payload['priceEur']);
        $invoice->setQuantity((string) $payload['quantity']);
        $invoice->setAmountUahExclVat((string) $payload['amountUahExclVat']);
        $invoice->setNomenclature((string) $payload['nomenclature']);

        if (array_key_exists('paidAt', $payload)) {
            $invoice->setPaidAt($this->parseDate($payload['paidAt'] !== null ? (string) $payload['paidAt'] : null, 'paidAt', true));
        }

        if (array_key_exists('note', $payload)) {
            $invoice->setNote($payload['note'] !== null ? (string) $payload['note'] : null);
        }

        $this->calculationService->calculateForInvoice($invoice);
        $this->validateEntity($invoice);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        return $this->json($invoice, Response::HTTP_CREATED);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $invoice = $this->invoiceRepository->getOrFail($id);
        $payload = $this->getJsonPayload($request);

        if (array_key_exists('contract', $payload) || array_key_exists('contractId', $payload)) {
            $rawContract = $payload['contract'] ?? $payload['contractId'] ?? null;
            if ($rawContract === null) {
                throw new BadRequestHttpException('Field "contract" cannot be null.');
            }
            $contractId = is_array($rawContract) ? ($rawContract['id'] ?? null) : $rawContract;
            if (!is_numeric($contractId)) {
                throw new BadRequestHttpException('Field "contract" must reference a valid contract ID.');
            }
            $contract = $this->contractRepository->getOrFail((int) $contractId);
            $invoice->setContract($contract);
        }

        if (array_key_exists('number', $payload)) {
            if ($payload['number'] === null) {
                throw new BadRequestHttpException('Field "number" cannot be null.');
            }
            $invoice->setNumber((string) $payload['number']);
        }

        if (array_key_exists('nomenclature', $payload)) {
            if ($payload['nomenclature'] === null) {
                throw new BadRequestHttpException('Field "nomenclature" cannot be null.');
            }
            $invoice->setNomenclature((string) $payload['nomenclature']);
        }

        if (array_key_exists('issued', $payload)) {
            $invoice->setIssued($this->parseDate($payload['issued'] !== null ? (string) $payload['issued'] : null, 'issued', false));
        }

        if (array_key_exists('paidAt', $payload)) {
            $invoice->setPaidAt($this->parseDate($payload['paidAt'] !== null ? (string) $payload['paidAt'] : null, 'paidAt', true));
        }

        if (array_key_exists('note', $payload)) {
            $invoice->setNote($payload['note'] !== null ? (string) $payload['note'] : null);
        }

        $recalculate = false;

        if (array_key_exists('priceEur', $payload)) {
            if ($payload['priceEur'] === null) {
                throw new BadRequestHttpException('Field "priceEur" cannot be null.');
            }
            $invoice->setPriceEur((string) $payload['priceEur']);
            $recalculate = true;
        }

        if (array_key_exists('quantity', $payload)) {
            if ($payload['quantity'] === null) {
                throw new BadRequestHttpException('Field "quantity" cannot be null.');
            }
            $invoice->setQuantity((string) $payload['quantity']);
            $recalculate = true;
        }

        if (array_key_exists('amountUahExclVat', $payload)) {
            if ($payload['amountUahExclVat'] === null) {
                throw new BadRequestHttpException('Field "amountUahExclVat" cannot be null.');
            }
            $invoice->setAmountUahExclVat((string) $payload['amountUahExclVat']);
            $recalculate = true;
        }

        if ($recalculate) {
            $this->calculationService->calculateForInvoice($invoice);
        }

        $this->validateEntity($invoice);
        $this->entityManager->flush();

        return $this->json($invoice);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $invoice = $this->invoiceRepository->getOrFail($id);

        $this->entityManager->remove($invoice);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
