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
    public function getInvoiceId(string $invoiceNumber)
    {
        try {
            return $this->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->where('i.invoiceNumber LIKE :invoiceNumber')
                ->setParameter('invoiceNumber', (string) $invoiceNumber)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * @return integer Returns an count integer
     */
    public function getTypeCounter(string $invoiceNumber, int $transactionTypeId, int $invoiceTypeId)
    {
        try {
            return $this->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->where('i.invoiceNumber LIKE :invoiceNumber')
                ->andWhere('i.transactionType LIKE :transactionTypeId or i.transactionType LIKE :transactionType')
                ->andWhere('i.invoiceType LIKE :invoiceTypeId or i.invoiceType LIKE :invoiceType')
                ->setParameter('invoiceNumber', (string) $invoiceNumber)
                ->setParameter('transactionTypeId', (string) $transactionTypeId)
                ->setParameter('transactionType', Invoice::TRANSACTION_TYPES[$transactionTypeId])
                ->setParameter('invoiceTypeId', (string) $invoiceTypeId)
                ->setParameter('invoiceType', Invoice::INVOICE_TYPES[$invoiceTypeId])
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

    /**
     * @return array Returns
     */
    public function getAllWithoutVcsdResponse(): array
    {
        try {
            return $this->createQueryBuilder('i')
                ->select('i')
                ->leftJoin('i.vcsdResponse', 'v')
                ->innerJoin('i.ecsdResponse', 'e')
                ->where('i.copied = false OR i.copied IS NULL')
                ->andWhere('v.id IS NULL')
                ->andWhere('e.message IS NULL')
                ->orderBy('i.id', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @return array Returns
     */
    public function getLastSignedInvoice()
    {
        try {
            return $this->createQueryBuilder('i')
                ->select('i')
                ->innerJoin('i.ecsdResponse', 'e')
                ->andWhere('e.message IS NULL')
                ->orderBy('i.id', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Exception $e) {
            return null;
        }
    }
}
