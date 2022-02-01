<?php

namespace App\Repository\Invoice;

use App\Entity\Invoice\CsdResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CsdResponse|null find($id, $lockMode = null, $lockVersion = null)
 * @method CsdResponse|null findOneBy(array $criteria, array $orderBy = null)
 * @method CsdResponse[]    findAll()
 * @method CsdResponse[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CsdResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CsdResponse::class);
    }

    // /**
    //  * @return CsdResponse[] Returns an array of CsdResponse objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CsdResponse
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
