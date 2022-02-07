<?php

namespace App\Repository\Invoice;

use App\Entity\Invoice\EcsdResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EcsdResponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method EcsdResponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method EcsdResponse[]    findAll()
 * @method EcsdResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EcsdResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EcsdResponse::class);
    }

}
