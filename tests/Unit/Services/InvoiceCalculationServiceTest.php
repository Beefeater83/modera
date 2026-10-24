<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\Invoice;
use App\Services\InvoiceCalculationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class InvoiceCalculationServiceTest extends TestCase
{
    private InvoiceCalculationService $service;

    protected function setUp(): void
    {
        $this->service = new InvoiceCalculationService();
    }

    public function testCalculateReturnsCorrectValues(): void
    {
        $result = $this->service->calculate('100.00', '2', '10000.00');

        self::assertSame([
            'amountEur' => '200.00',
            'exchangeRate' => '50.00',
            'vatAmount' => '2000.00',
            'amountUah' => '12000.00',
        ], $result);
    }

    public function testCalculateRoundsValuesToTwoDecimalPlaces(): void
    {
        $result = $this->service->calculate('10.00', '3', '100.00');

        self::assertSame([
            'amountEur' => '30.00',
            'exchangeRate' => '3.33',
            'vatAmount' => '20.00',
            'amountUah' => '120.00',
        ], $result);
    }

    public function testCalculateAcceptsZeroAmountWithoutVat(): void
    {
        $result = $this->service->calculate('10.00', '2', '0');

        self::assertSame([
            'amountEur' => '20.00',
            'exchangeRate' => '0.00',
            'vatAmount' => '0.00',
            'amountUah' => '0.00',
        ], $result);
    }

    public function testCalculateRejectsZeroPrice(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Price in EUR must be a positive number.');

        $this->service->calculate('0', '2', '100');
    }

    public function testCalculateRejectsNegativePrice(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Price in EUR must be a positive number.');

        $this->service->calculate('-10', '2', '100');
    }

    public function testCalculateRejectsNonNumericPrice(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Price in EUR must be a positive number.');

        $this->service->calculate('abc', '2', '100');
    }

    public function testCalculateRejectsZeroQuantity(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Quantity must be a positive number.');

        $this->service->calculate('10', '0', '100');
    }

    public function testCalculateRejectsNegativeQuantity(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Quantity must be a positive number.');

        $this->service->calculate('10', '-2', '100');
    }

    public function testCalculateRejectsNonNumericQuantity(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Quantity must be a positive number.');

        $this->service->calculate('10', 'abc', '100');
    }

    public function testCalculateRejectsNegativeAmountUahExclVat(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            'Amount in UAH excluding VAT must be a non-negative number.'
        );

        $this->service->calculate('10', '2', '-100');
    }

    public function testCalculateRejectsNonNumericAmountUahExclVat(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            'Amount in UAH excluding VAT must be a non-negative number.'
        );

        $this->service->calculate('10', '2', 'abc');
    }

    public function testCalculateRejectsAmountEurThatRoundsToZero(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(
            'Calculated amount in EUR must be greater than zero to calculate exchange rate.'
        );

        $this->service->calculate('0.001', '1', '100');
    }

    public function testCalculateForInvoiceUpdatesDerivedFields(): void
    {
        $invoice = new Invoice();
        $invoice->setPriceEur('100.00');
        $invoice->setQuantity('2');
        $invoice->setAmountUahExclVat('10000.00');

        $this->service->calculateForInvoice($invoice);

        self::assertSame('200.00', $invoice->getAmountEur());
        self::assertSame('50.00', $invoice->getExchangeRate());
        self::assertSame('2000.00', $invoice->getVatAmount());
        self::assertSame('12000.00', $invoice->getAmountUah());
    }

    public function testRoundPositiveValue(): void
    {
        self::assertSame('1.24', InvoiceCalculationService::round('1.235'));
    }

    public function testRoundNegativeValue(): void
    {
        self::assertSame('-1.24', InvoiceCalculationService::round('-1.235'));
    }

    public function testRoundValueWithCustomScale(): void
    {
        self::assertSame('1.235', InvoiceCalculationService::round('1.23456', 3));
    }
}
