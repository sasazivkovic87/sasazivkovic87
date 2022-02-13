<?php

namespace App\Entity;

use App\Repository\InvoiceResponseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InvoiceResponseRepository::class)
 */
class InvoiceResponse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $ecsdVerificationUrl;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $vcsdVerificationUrl;

    /**
     * @ORM\Column(type="text")
     */
    private $jsonResponse;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJsonResponse(): ?string
    {
        return $this->jsonResponse;
    }

    public function setJsonResponse(string $jsonResponse): self
    {
        $this->jsonResponse = $jsonResponse;

        return $this;
    }

    public function getVcsdVerificationUrl(): ?string
    {
        return $this->vcsdVerificationUrl;
    }

    public function setVcsdVerificationUrl(?string $vcsdVerificationUrl): self
    {
        $this->vcsdVerificationUrl = $vcsdVerificationUrl;

        return $this;
    }

    public function getEcsdVerificationUrl(): ?string
    {
        return $this->ecsdVerificationUrl;
    }

    public function setEcsdVerificationUrl(string $ecsdVerificationUrl): self
    {
        $this->ecsdVerificationUrl = $ecsdVerificationUrl;

        return $this;
    }
}
