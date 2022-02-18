<?php

namespace App\Repository;

use App\Entity\PinVerification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PinVerification|null find($id, $lockMode = null, $lockVersion = null)
 * @method PinVerification|null findOneBy(array $criteria, array $orderBy = null)
 * @method PinVerification[]    findAll()
 * @method PinVerification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PinVerificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PinVerification::class);
    }

	public function deleteAll() {
        $query = $this->createQueryBuilder('pv')
                 ->delete()
                 ->getQuery()
                 ->execute();
        return $query;
	}

}
