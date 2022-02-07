<?php

namespace App\Entity\Tax;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\Tax\TaxRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
/**
 * @ApiResource(
 *     normalizationContext={"groups"={"tax", "tax_category", "tax_rate"}},
 *     denormalizationContext={"groups"={"tax_post", "tax_category", "tax_rate"}},
 *     attributes={"order"={"id": "DESC"}}
 * )
 *
 * @ORM\Entity(repositoryClass=TaxRepository::class)
 *
 */
class Tax
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"tax"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"tax", "tax_post"})
     */
    private $validFrom;
    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"tax", "tax_post"})
     */
    private $groupId;

    /**
     * @ORM\OneToMany(targetEntity=TaxCategory::class, mappedBy="tax", cascade={"persist"})
     * @Groups({"tax", "tax_post"})
     */
    private $taxCategories;

    public function __construct()
    {
        $this->taxCategories = new ArrayCollection();
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

    public function getValidFrom(): ?string
    {
        return $this->validFrom;
    }

    public function setValidFrom(string $validFrom): self
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    public function setGroupId(?int $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @return Collection|TaxCategory[]
     */
    public function getTaxCategories(): Collection
    {
        return $this->taxCategories;
    }

    public function addTaxCategory(TaxCategory $taxCategory): self
    {
        if (!$this->taxCategories->contains($taxCategory)) {
            $this->taxCategories[] = $taxCategory;
            $taxCategory->setTax($this);
        }

        return $this;
    }

    public function removeTaxCategory(TaxCategory $taxCategory): self
    {
        if ($this->taxCategories->contains($taxCategory)) {
            $this->taxCategories->removeElement($taxCategory);
            // set the owning side to null (unless already changed)
            if ($taxCategory->getTax() === $this) {
                $taxCategory->setTax(null);
            }
        }

        return $this;
    }
}
