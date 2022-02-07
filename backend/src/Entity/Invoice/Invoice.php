<?php

namespace App\Entity\Invoice;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\Invoice\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiFilter;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
/**
 * @ApiResource(
 *     normalizationContext={"groups"={"invoice", "invoice_item", "invoice_payment", "invoice_option"}},
 *     denormalizationContext={"groups"={"invoice_post", "invoice_item", "invoice_payment", "invoice_option"}},
 *     attributes={"order"={"id": "DESC"}}
 * )
 *
 * @ORM\Entity(repositoryClass=InvoiceRepository::class)
 *
 */
class Invoice
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"invoice"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"invoice", "invoice_post"})
     */
    private $dateAndTimeOfIssue;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"invoice", "invoice_post"})
     */
    private $cashier;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"invoice", "invoice_post"})
     */
    private $buyerId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"invoice", "invoice_post"})
     */
    private $buyerCostCenterId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"invoice", "invoice_post"})
     */
    private $invoiceType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"invoice", "invoice_post"})
     */
    private $transactionType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"invoice", "invoice_post"})
     */
    private $invoiceNumber;

    /**
     * @ORM\ManyToOne(targetEntity=InvoiceOption::class, inversedBy="invoices", cascade={"persist"})
     * @ORM\JoinColumn(name="options_id", referencedColumnName="id", onDelete="CASCADE")
     * @Groups({"invoice", "invoice_post"})
     */
    private $options;

    /**
     * @ORM\OneToMany(targetEntity=InvoiceItem::class, mappedBy="invoice", cascade={"persist"})
     * @Groups({"invoice", "invoice_post"})
     */
    private $items;

    /**
     * @ORM\OneToMany(targetEntity=InvoicePayment::class, mappedBy="invoice", cascade={"persist"})
     * @Groups({"invoice", "invoice_post"})
     */
    private $payment;

    /**
     * @ORM\OneToOne(targetEntity=EcsdResponse::class, mappedBy="invoice", cascade={"persist"})
     */
    private $ecsdResponse;

    /**
     * @ORM\OneToOne(targetEntity=VcsdResponse::class, mappedBy="invoice", cascade={"persist"})
     */
    private $vcsdResponse;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->payment = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getDateAndTimeOfIssue(): ?string
    {
        return $this->dateAndTimeOfIssue;
    }

    public function setDateAndTimeOfIssue(?string $dateAndTimeOfIssue): self
    {
        $this->dateAndTimeOfIssue = $dateAndTimeOfIssue;

        return $this;
    }

    public function getCashier(): ?string
    {
        return $this->cashier;
    }

    public function setCashier(?string $cashier): self
    {
        $this->cashier = $cashier;

        return $this;
    }

    public function getBuyerId(): ?string
    {
        return $this->buyerId;
    }

    public function setBuyerId(?string $buyerId): self
    {
        $this->buyerId = $buyerId;

        return $this;
    }

    public function getBuyerCostCenterId(): ?string
    {
        return $this->buyerCostCenterId;
    }

    public function setBuyerCostCenterId(?string $buyerCostCenterId): self
    {
        $this->buyerCostCenterId = $buyerCostCenterId;

        return $this;
    }

    public function getInvoiceType(): ?string
    {
        return $this->invoiceType;
    }

    public function setInvoiceType(?string $invoiceType): self
    {
        $this->invoiceType = $invoiceType;

        return $this;
    }

    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    public function setTransactionType(?string $transactionType): self
    {
        $this->transactionType = $transactionType;

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getOptions(): ?InvoiceOption
    {
        return $this->options;
    }

    public function setOptions(?InvoiceOption $Options): self
    {
        $this->options = $Options;

        return $this;
    }

    /**
     * @return Collection|InvoiceItem[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(InvoiceItem $invoiceItem): self
    {
        if (!$this->items->contains($invoiceItem)) {
            $this->items[] = $invoiceItem;
            $invoiceItem->setInvoice($this);
        }

        return $this;
    }

    public function removeItem(InvoiceItem $invoiceItem): self
    {
        if ($this->items->contains($invoiceItem)) {
            $this->items->removeElement($invoiceItem);
            // set the owning side to null (unless already changed)
            if ($invoiceItem->getInvoice() === $this) {
                $invoiceItem->setInvoice(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|InvoicePayment[]
     */
    public function getPayment(): Collection
    {
        return $this->payment;
    }

    public function addPayment(InvoicePayment $invoicePayment): self
    {
        if (!$this->payment->contains($invoicePayment)) {
            $this->payment[] = $invoicePayment;
            $invoicePayment->setInvoice($this);
        }

        return $this;
    }

    public function removePayment(InvoicePayment $invoicePayment): self
    {
        if ($this->payment->contains($invoicePayment)) {
            $this->payment->removeElement($invoicePayment);
            // set the owning side to null (unless already changed)
            if ($invoicePayment->getInvoice() === $this) {
                $invoicePayment->setInvoice(null);
            }
        }

        return $this;
    }

    public function getEcsdResponse(): ?EcsdResponse
    {
        return $this->ecsdResponse;
    }

    public function setEcsdResponse(?EcsdResponse $ecsdResponse): self
    {
        $this->ecsdResponse = $ecsdResponse;

        return $this;
    }

    public function getVcsdResponse(): ?VcsdResponse
    {
        return $this->vcsdResponse;
    }

    public function setVcsdResponse(?VcsdResponse $vcsdResponse): self
    {
        $this->vcsdResponse = $vcsdResponse;

        return $this;
    }
}
