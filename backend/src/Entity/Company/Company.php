<?php

namespace App\Entity\Company;

use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\Company\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"company"}}
 * )
 * @ORM\Entity(repositoryClass=CompanyRepository::class)
 */
class Company
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"company"})
     */
    private $id;

     /**
     * @ORM\Column(type="integer")
     */
    private $centralId;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Groups({"company", "company_post"})
     * @Assert\NotBlank(message="Unesite ime organizacije")
     */
    private $name;

    /**
     * @ORM\Column(type="text", length=255, nullable=false)
     * @Groups({"company"})
     */
    private $pib;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"company"})
     */
    private $pdv;

    public function __construct()
    {
        $this->userCompanies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

      public function getCentralId(): ?int
    {
        return $this->centralId;
    }

    public function setCentralId(int $centralId): self
    {
        $this->centralId = $centralId;

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

    public function getPib(): ?string
    {
        return $this->pib;
    }

    public function setPib(string $pib): self
    {
        $this->pib = $pib;

        return $this;
    }

    public function getPdv()
    {
        return $this->pdv;
    }

    public function setPdv($pdv)
    {
        $this->pdv = $pdv;

        return $this;
    }
}
