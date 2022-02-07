<?php

namespace App\Repository\Tax;

use App\Entity\Tax\TaxRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TaxRate|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaxRate|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaxRate[]    findAll()
 * @method TaxRate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaxRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxRate::class);
    }

}
