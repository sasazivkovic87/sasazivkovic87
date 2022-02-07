<?php

namespace App\Repository\Invoice;

use App\Entity\Invoice\VcsdResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VcsdResponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method VcsdResponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method VcsdResponse[]    findAll()
 * @method VcsdResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VcsdResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VcsdResponse::class);
    }

}
