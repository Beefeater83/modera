<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Invoice;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InvoiceCalculationService
{
    /**
     * Calculates derived fields for an Invoice entity:
     * - amountEur = priceEur × quantity
     * - exchangeRate = amountUahExclVat / amountEur
     * - vatAmount = amountUahExclVat × 0.20
     * - amountUah = amountUahExclVat + vatAmount
     */
    public function calculateForInvoice(Invoice $invoice): void
    {
        $priceEur = $invoice->getPriceEur();
        $quantity = $invoice->getQuantity();
        $amountUahExclVat = $invoice->getAmountUahExclVat();

        $result = $this->calculate($priceEur, $quantity, $amountUahExclVat);

        $invoice->setAmountEur($result['amountEur']);
        $invoice->setExchangeRate($result['exchangeRate']);
        $invoice->setVatAmount($result['vatAmount']);
        $invoice->setAmountUah($result['amountUah']);
    }

    /**
     * @return array{amountEur: string, exchangeRate: string, vatAmount: string, amountUah: string}
     */
    public function calculate(string $priceEur, string $quantity, string $amountUahExclVat): array
    {
        $this->validateInputs($priceEur, $quantity, $amountUahExclVat);

        $amountEur = self::round(bcmul($priceEur, $quantity, 6), 2);

        if (bccomp($amountEur, '0.00', 4) <= 0) {
            throw new BadRequestHttpException('Calculated amount in EUR must be greater than zero to calculate exchange rate.');
        }

        $exchangeRate = self::round(bcdiv($amountUahExclVat, $amountEur, 6), 2);
        $vatAmount = self::round(bcmul($amountUahExclVat, '0.20', 6), 2);
        $amountUah = self::round(bcadd($amountUahExclVat, $vatAmount, 6), 2);

        return [
            'amountEur' => $amountEur,
            'exchangeRate' => $exchangeRate,
            'vatAmount' => $vatAmount,
            'amountUah' => $amountUah,
        ];
    }

    private function validateInputs(string $priceEur, string $quantity, string $amountUahExclVat): void
    {
        if (!is_numeric($priceEur) || bccomp($priceEur, '0', 4) <= 0) {
            throw new BadRequestHttpException('Price in EUR must be a positive number.');
        }

        if (!is_numeric($quantity) || bccomp($quantity, '0', 4) <= 0) {
            throw new BadRequestHttpException('Quantity must be a positive number.');
        }

        if (!is_numeric($amountUahExclVat) || bccomp($amountUahExclVat, '0', 4) < 0) {
            throw new BadRequestHttpException('Amount in UAH excluding VAT must be a non-negative number.');
        }
    }

    public static function round(string $val, int $scale = 2): string
    {
        if (function_exists('bcround')) {
            return bcround($val, $scale);
        }

        $factor = bcpow('10', (string) $scale);
        if (bccomp($val, '0', 8) >= 0) {
            $shifted = bcadd(bcmul($val, $factor, 8), '0.5', 8);
        } else {
            $shifted = bcsub(bcmul($val, $factor, 8), '0.5', 8);
        }

        $integerPart = strtok($shifted, '.');
        if ($integerPart === false || $integerPart === '') {
            $integerPart = '0';
        }

        return bcdiv($integerPart, $factor, $scale);
    }
}
