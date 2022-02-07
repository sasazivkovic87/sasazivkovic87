<?php

namespace App\Entity\Tax;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\Tax\TaxRateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"tax_rate"}},
 *     denormalizationContext={"groups"={"tax_rate"}},
 *     attributes={"order"={"id": "DESC"}}
 * )
 * @ORM\Entity(repositoryClass=TaxRateRepository::class)
 */
class TaxRate
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"tax_rate"})
     */
    private $rate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"tax_rate"})
     */
    private $label;

    /**
     * @ORM\ManyToOne(targetEntity=TaxCategory::class, inversedBy="taxRates")
     * @ORM\JoinColumn(name="tax_category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $taxCategory;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getTaxCategory(): ?TaxCategory
    {
        return $this->taxCategory;
    }

    public function setTaxCategory(?TaxCategory $taxCategory): self
    {
        $this->taxCategory = $taxCategory;

        return $this;
    }
}
