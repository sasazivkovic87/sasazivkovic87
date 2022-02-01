<?php

namespace App\Entity\Traits;

use App\Entity\Company\Company;
use Doctrine\ORM\Mapping as ORM;

trait CompanyTrait
{
    /**
     * @ORM\ManyToOne(targetEntity=Company::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $company;

    /**
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * @param Company|null $company
     * @return $this
     */
    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }
}
