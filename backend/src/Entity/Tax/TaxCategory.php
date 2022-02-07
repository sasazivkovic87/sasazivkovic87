<?php

namespace App\Entity\Tax;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\Tax\TaxCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"tax_category", "tax_rate"}},
 *     denormalizationContext={"groups"={"tax_category", "tax_rate"}},
 *     attributes={"order"={"id": "DESC"}}
 * )
 * @ORM\Entity(repositoryClass=TaxCategoryRepository::class)
 */
class TaxCategory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Tax::class, inversedBy="taxCategories")
     * @ORM\JoinColumn(name="tax_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $tax;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"tax_category"})
     */
    private $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"tax_category"})
     */
    private $categoryType;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"tax_category"})
     */
    private $orderId;

    /**
     * @ORM\OneToMany(targetEntity=TaxRate::class, mappedBy="taxCategory", cascade={"persist"})
     * @Groups({"tax_category"})
     */
    private $taxRates;

    public function __construct()
    {
        $this->taxRates = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategoryType(): ?int
    {
        return $this->categoryType;
    }

    public function setCategoryType(?int $categoryType): self
    {
        $this->categoryType = $categoryType;

        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(?int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getTax(): ?Tax
    {
        return $this->tax;
    }

    public function setTax(?Tax $tax): self
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * @return Collection|TaxRate[]
     */
    public function getTaxRates(): Collection
    {
        return $this->taxRates;
    }

    public function addTaxRate(TaxRate $taxRate): self
    {
        if (!$this->taxRates->contains($taxRate)) {
            $this->taxRates[] = $taxRate;
            $taxRate->setTaxCategory($this);
        }

        return $this;
    }

    public function removeTaxRate(TaxRate $taxRate): self
    {
        if ($this->taxRates->contains($taxRate)) {
            $this->taxRates->removeElement($taxRate);
            // set the owning side to null (unless already changed)
            if ($taxRate->getTaxCategory() === $this) {
                $taxRate->setTaxCategory(null);
            }
        }

        return $this;
    }
}
