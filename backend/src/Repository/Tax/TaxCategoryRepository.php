<?php

namespace App\Repository\Tax;

use App\Entity\Tax\TaxCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TaxCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaxCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaxCategory[]    findAll()
 * @method TaxCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaxCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxCategory::class);
    }

}
