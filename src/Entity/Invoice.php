<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoice')]
class Invoice implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Invoice number cannot be blank.')]
    private string $number;

    #[ORM\ManyToOne(targetEntity: Contract::class, inversedBy: 'invoices')]
    #[ORM\JoinColumn(name: 'contract_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    #[Assert\NotNull(message: 'Contract reference is required.')]
    private Contract $contract;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'Issued date is required.')]
    private \DateTimeImmutable $issued;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    #[Assert\NotBlank(message: 'Price in EUR is required.')]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Price in EUR must be a valid decimal number with up to 2 decimal places.')]
    private string $priceEur;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 1)]
    #[Assert\NotBlank(message: 'Quantity is required.')]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1})?$/', message: 'Quantity must be a valid decimal number with up to 1 decimal place.')]
    private string $quantity;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    private string $amountEur;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    #[Assert\NotBlank(message: 'Amount in UAH excluding VAT is required.')]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Amount in UAH excluding VAT must be a valid decimal number with up to 2 decimal places.')]
    private string $amountUahExclVat;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    private string $exchangeRate;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    private string $vatAmount;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    private string $amountUah;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Nomenclature cannot be blank.')]
    private string $nomenclature;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getContract(): Contract
    {
        return $this->contract;
    }

    public function setContract(Contract $contract): static
    {
        $this->contract = $contract;

        return $this;
    }

    public function getIssued(): \DateTimeImmutable
    {
        return $this->issued;
    }

    public function setIssued(\DateTimeImmutable $issued): static
    {
        $this->issued = $issued;

        return $this;
    }

    public function getPriceEur(): string
    {
        return $this->priceEur;
    }

    public function setPriceEur(string $priceEur): static
    {
        $this->priceEur = $priceEur;

        return $this;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getAmountEur(): string
    {
        return $this->amountEur;
    }

    public function setAmountEur(string $amountEur): static
    {
        $this->amountEur = $amountEur;

        return $this;
    }

    public function getAmountUahExclVat(): string
    {
        return $this->amountUahExclVat;
    }

    public function setAmountUahExclVat(string $amountUahExclVat): static
    {
        $this->amountUahExclVat = $amountUahExclVat;

        return $this;
    }

    public function getExchangeRate(): string
    {
        return $this->exchangeRate;
    }

    public function setExchangeRate(string $exchangeRate): static
    {
        $this->exchangeRate = $exchangeRate;

        return $this;
    }

    public function getVatAmount(): string
    {
        return $this->vatAmount;
    }

    public function setVatAmount(string $vatAmount): static
    {
        $this->vatAmount = $vatAmount;

        return $this;
    }

    public function getAmountUah(): string
    {
        return $this->amountUah;
    }

    public function setAmountUah(string $amountUah): static
    {
        $this->amountUah = $amountUah;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getNomenclature(): string
    {
        return $this->nomenclature;
    }

    public function setNomenclature(string $nomenclature): static
    {
        $this->nomenclature = $nomenclature;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'contractId' => $this->contract->getId(),
            'issued' => $this->issued->format('Y-m-d'),
            'priceEur' => $this->priceEur,
            'quantity' => $this->quantity,
            'amountEur' => $this->amountEur,
            'amountUahExclVat' => $this->amountUahExclVat,
            'exchangeRate' => $this->exchangeRate,
            'vatAmount' => $this->vatAmount,
            'amountUah' => $this->amountUah,
            'paidAt' => $this->paidAt?->format('Y-m-d'),
            'nomenclature' => $this->nomenclature,
            'note' => $this->note,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
