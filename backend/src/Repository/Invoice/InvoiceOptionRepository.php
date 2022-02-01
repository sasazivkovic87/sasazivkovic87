<?php

namespace App\Repository\Invoice;

use App\Entity\Invoice\InvoiceOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InvoiceOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvoiceOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvoiceOption[]    findAll()
 * @method InvoiceOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceOption::class);
    }

}
