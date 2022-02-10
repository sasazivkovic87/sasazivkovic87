<?php

namespace App\Repository\Invoice;

use App\Entity\Invoice\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * @return integer Returns an count integer
     */
    public function getCountOfObjects(int $invoiceTypeId, int $transactionTypeId)
    {
        try {
            return $this->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->where('i.invoiceType LIKE :invoiceTypeId or i.invoiceType LIKE :invoiceType')
                ->andWhere('i.transactionType LIKE :transactionTypeId or i.transactionType LIKE :transactionType')
                ->setParameter('invoiceTypeId', (string) $invoiceTypeId)
                ->setParameter('invoiceType', Invoice::INVOICE_TYPES[$invoiceTypeId])
                ->setParameter('transactionTypeId', (string) $transactionTypeId)
                ->setParameter('transactionType', Invoice::TRANSACTION_TYPES[$transactionTypeId])
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * @return integer Returns an count integer
     */
    public function getTotalAmount(Invoice $invoice)
    {
        try {
            return $this->createQueryBuilder('i')
                ->select('SUM(ii.totalAmount)')
            	->innerJoin('i.items', 'ii')
                ->where('ii.invoice = :invoice')
                ->setParameter('invoice', $invoice)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
