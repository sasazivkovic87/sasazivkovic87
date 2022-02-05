<?php

namespace App\Entity\Invoice;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\Invoice\InvoicePaymentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"invoice_payment"}},
 *     denormalizationContext={"groups"={"invoice_payment"}},
 )
 * @ORM\Entity(repositoryClass=InvoicePaymentRepository::class)
 */
class InvoicePayment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Invoice::class, inversedBy="items")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $invoice;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"invoice_payment"})
     */
    private $paymentType;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"invoice_payment"})
     */
    private $amount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getPaymentType()
    {
        return $this->paymentType;
    }

    public function setPaymentType($paymentType): self
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        $this->invoice = $invoice;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }
}
