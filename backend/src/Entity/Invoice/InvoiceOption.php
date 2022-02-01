<?php

namespace App\Entity\Invoice;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\Invoice\InvoiceOptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"invoice_option"}},
 *     denormalizationContext={"groups"={"invoice_option"}},
 )
 * @ORM\Entity(repositoryClass=InvoiceOptionRepository::class)
 */
class InvoiceOption
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"invoice_option"})
     */
    private $omitQRCodeGen;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"invoice_option"})
     */
    private $omitTextualRepresentation;

    /**
     * @ORM\OneToMany(targetEntity=Invoice::class, mappedBy="options", cascade={"persist"})
     */
    private $invoices;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getOmitTextualRepresentation(): ?string
    {
        return $this->omitTextualRepresentation;
    }

    public function setOmitTextualRepresentation(?string $omitTextualRepresentation): self
    {
        $this->omitTextualRepresentation = $omitTextualRepresentation;

        return $this;
    }

    public function getOmitQRCodeGen(): ?string
    {
        return $this->omitQRCodeGen;
    }

    public function setOmitQRCodeGen(string $omitQRCodeGen): self
    {
        $this->omitQRCodeGen = $omitQRCodeGen;

        return $this;
    }
}
