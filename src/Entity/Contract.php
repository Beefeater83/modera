<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\Table(name: 'contract')]
class Contract implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Customer cannot be blank.')]
    private string $customer;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Contract number cannot be blank.')]
    private string $number;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'Issued date is required.')]
    private \DateTimeImmutable $issued;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $signed = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2)]
    #[Assert\NotBlank(message: 'Amount in EUR is required.')]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Amount in EUR must be a valid decimal number with up to 2 decimal places.')]
    private string $amountEur;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Licence price in EUR must be a valid decimal number with up to 2 decimal places.')]
    private ?string $licencePriceEur = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $licenceSince = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Subject cannot be blank.')]
    private string $subject;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'contract')]
    private Collection $invoices;

    public function __construct()
    {
        $this->invoices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): string
    {
        return $this->customer;
    }

    public function setCustomer(string $customer): static
    {
        $this->customer = $customer;

        return $this;
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

    public function getIssued(): \DateTimeImmutable
    {
        return $this->issued;
    }

    public function setIssued(\DateTimeImmutable $issued): static
    {
        $this->issued = $issued;

        return $this;
    }

    public function getSigned(): ?\DateTimeImmutable
    {
        return $this->signed;
    }

    public function setSigned(?\DateTimeImmutable $signed): static
    {
        $this->signed = $signed;

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

    public function getLicencePriceEur(): ?string
    {
        return $this->licencePriceEur;
    }

    public function setLicencePriceEur(?string $licencePriceEur): static
    {
        $this->licencePriceEur = $licencePriceEur;

        return $this;
    }

    public function getLicenceSince(): ?\DateTimeImmutable
    {
        return $this->licenceSince;
    }

    public function setLicenceSince(?\DateTimeImmutable $licenceSince): static
    {
        $this->licenceSince = $licenceSince;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

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

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer' => $this->customer,
            'number' => $this->number,
            'issued' => $this->issued->format('Y-m-d'),
            'signed' => $this->signed?->format('Y-m-d'),
            'amountEur' => $this->amountEur,
            'licencePriceEur' => $this->licencePriceEur,
            'licenceSince' => $this->licenceSince?->format('Y-m-d'),
            'subject' => $this->subject,
            'note' => $this->note,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
