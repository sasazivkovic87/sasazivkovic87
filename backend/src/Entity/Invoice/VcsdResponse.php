<?php

namespace App\Entity\Invoice;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Invoice\Invoice;
use App\Repository\Invoice\VcsdResponseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=VcsdResponseRepository::class)
 */
class VcsdResponse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $requestedBy;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sdcDateTime;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $invoiceCounter;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $invoiceCounterExtension;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $invoiceNumber;

    /**
     * @ORM\Column(type="json", length=1000, nullable=true)
     */
    private $taxItems;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    private $verificationUrl;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $verificationQRCode;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $journal;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $messages;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $signedBy;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $encryptedInternalData;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $signature;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $totalCounter;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $transactionTypeCounter;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $totalAmount;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $taxGroupRevision;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $businessName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tin;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $locationName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $district;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $mrc;

    /**
     * @ORM\Column(type="json", length=1000, nullable=true)
     */
    private $modelState;

    /**
     * @ORM\OneToOne(targetEntity=Invoice::class, inversedBy="vcsdResponse")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $invoice;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $message;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestedBy(): ?string
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?string $requestedBy): self
    {
        $this->requestedBy = $requestedBy;

        return $this;
    }

    public function getSdcDateTime(): ?string
    {
        return $this->sdcDateTime;
    }

    public function setSdcDateTime(?string $sdcDateTime): self
    {
        $this->sdcDateTime = $sdcDateTime;

        return $this;
    }

    public function getInvoiceCounter(): ?string
    {
        return $this->invoiceCounter;
    }

    public function setInvoiceCounter(?string $invoiceCounter): self
    {
        $this->invoiceCounter = $invoiceCounter;

        return $this;
    }

    public function getInvoiceCounterExtension(): ?string
    {
        return $this->invoiceCounterExtension;
    }

    public function setInvoiceCounterExtension(?string $invoiceCounterExtension): self
    {
        $this->invoiceCounterExtension = $invoiceCounterExtension;

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

    public function getTaxItems(): ?array
    {
        return json_decode($this->taxItems, true);
    }

    public function setTaxItems(array $taxItems): self
    {
        $this->taxItems = json_encode($taxItems);

        return $this;
    }

    public function getVerificationUrl(): ?string
    {
        return $this->verificationUrl;
    }

    public function setVerificationUrl(?string $verificationUrl): self
    {
        $this->verificationUrl = $verificationUrl;

        return $this;
    }

    public function getVerificationQRCode(): ?string
    {
        return $this->verificationQRCode;
    }

    public function setVerificationQRCode(?string $verificationQRCode): self
    {
        $this->verificationQRCode = $verificationQRCode;

        return $this;
    }

    public function getJournal(): ?string
    {
        return $this->journal;
    }

    public function setJournal(?string $journal): self
    {
        $this->journal = $journal;

        return $this;
    }

    public function getMessages(): ?string
    {
        return $this->messages;
    }

    public function setMessages(?string $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    public function getSignedBy(): ?string
    {
        return $this->signedBy;
    }

    public function setSignedBy(?string $signedBy): self
    {
        $this->signedBy = $signedBy;

        return $this;
    }

    public function getEncryptedInternalData(): ?string
    {
        return $this->encryptedInternalData;
    }

    public function setEncryptedInternalData(string $encryptedInternalData): self
    {
        $this->encryptedInternalData = $encryptedInternalData;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): self
    {
        $this->signature = $signature;

        return $this;
    }

    public function getTotalCounter(): ?int
    {
        return $this->totalCounter;
    }

    public function setTotalCounter(?int $totalCounter): self
    {
        $this->totalCounter = $totalCounter;

        return $this;
    }

    public function getTransactionTypeCounter(): ?int
    {
        return $this->transactionTypeCounter;
    }

    public function setTransactionTypeCounter(?int $transactionTypeCounter): self
    {
        $this->transactionTypeCounter = $transactionTypeCounter;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getTaxGroupRevision(): ?int
    {
        return $this->taxGroupRevision;
    }

    public function setTaxGroupRevision(?int $taxGroupRevision): self
    {
        $this->taxGroupRevision = $taxGroupRevision;

        return $this;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(?string $businessName): self
    {
        $this->businessName = $businessName;

        return $this;
    }

    public function getTin(): ?string
    {
        return $this->tin;
    }

    public function setTin(?string $tin): self
    {
        $this->tin = $tin;

        return $this;
    }

    public function getLocationName(): ?string
    {
        return $this->locationName;
    }

    public function setLocationName(?string $locationName): self
    {
        $this->locationName = $locationName;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): self
    {
        $this->district = $district;

        return $this;
    }

    public function getMrc(): ?string
    {
        return $this->mrc;
    }

    public function setMrc(?string $mrc): self
    {
        $this->mrc = $mrc;

        return $this;
    }

    public function getModelState(): ?array
    {
        return json_decode($this->modelState, true);
    }

    public function setModelState(?array $modelState): self
    {
        $this->modelState = json_encode($modelState);

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

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
}
