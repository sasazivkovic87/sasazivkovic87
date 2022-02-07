<?php

namespace App\Entity\Organization;

use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\Organization\OrganizationRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"organization"}},
 *     denormalizationContext={"groups"={"organization_post"}}
 * )
 * @ORM\Entity(repositoryClass=OrganizationRepository::class)
 */
class Organization
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"organization", "organization_post"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"organization", "organization_post"})
     */
    private $organizationName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"organization", "organization_post"})
     */
    private $serverTimeZone;

    /**
     * @ORM\Column(type="text", length=255, nullable=true)
     * @Groups({"organization", "organization_post"})
     */
    private $street;

    /**
     * @ORM\Column(type="text", length=255, nullable=true)
     * @Groups({"organization", "organization_post"})
     */
    private $city;

    /**
     * @ORM\Column(type="text", length=255, nullable=true)
     * @Groups({"organization", "organization_post"})
     */
    private $country;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Groups({"organization", "organization_post"})
     */
    private $endpoints;

    /**
     * @ORM\Column(type="text", length=255, nullable=true)
     * @Groups({"organization", "organization_post"})
     */
    private $environmentName;

    /**
     * @ORM\Column(type="text", length=255, nullable=true)
     * @Groups({"organization", "organization_post"})
     */
    private $logo;

    /**
     * @ORM\Column(type="text", length=255, nullable=true)
     * @Groups({"organization", "organization_post"})
     */
    private $ntpServer;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Groups({"organization", "organization_post"})
     */
    private $supportedLanguages;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="organization", cascade={"persist"})
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(string $organizationName): self
    {
        $this->organizationName = $organizationName;

        return $this;
    }

    public function getServerTimeZone(): ?string
    {
        return $this->serverTimeZone;
    }

    public function setServerTimeZone(string $serverTimeZone): self
    {
        $this->serverTimeZone = $serverTimeZone;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getEndpoints(): ?array
    {
        return $this->endpoints;
    }

    public function setEndpoints($endpoints): self
    {
        $this->endpoints = $endpoints;

        return $this;
    }

    public function getEnvironmentName(): ?string
    {
        return $this->environmentName;
    }

    public function setEnvironmentName(string $environmentName): self
    {
        $this->environmentName = $environmentName;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getNtpServer(): ?string
    {
        return $this->ntpServer;
    }

    public function setNtpServer(string $ntpServer): self
    {
        $this->ntpServer = $ntpServer;

        return $this;
    }

    public function getSupportedLanguages(): ?array
    {
        return $this->supportedLanguages;
    }

    public function setSupportedLanguages($supportedLanguages): self
    {
        $this->supportedLanguages = $supportedLanguages;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setOrganization($this);
        }

        return $this;
    }
}
