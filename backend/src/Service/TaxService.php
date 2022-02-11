<?php

namespace App\Service;

use App\Entity\Invoice\Invoice;
use App\Entity\Tax\Tax;
use App\Entity\Tax\TaxCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class TaxService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;

    }

    public function getTaxItems(Invoice $invoice): array
    {   
        $taxItems = [];
        foreach ($invoice->getItems() as $item) {
            foreach ($item->getLabels() as $label) {
                $taxResult = $this->entityManager->getRepository(TaxCategory::class)->findByLabel($label);

                if (!isset($taxItems[$taxResult['name'] . $taxResult['categoryType'] . $taxResult['label']])) {
                    $taxItems[$taxResult['name'] . $taxResult['categoryType'] . $taxResult['label']] = [
                        'categoryType' => (int) $taxResult['categoryType'],
                        'label' => $taxResult['label'],
                        'amount' => 0,
                        'rate' => $taxResult['rate'],
                        'categoryName' => $taxResult['name']
                    ];
                }
                $taxItems[$taxResult['name'] . $taxResult['categoryType'] . $taxResult['label']]['amount'] += $this->calculateTaxAmount($label, (float) $item->getTotalAmount(), $item->getLabels());
            }
        }

        $taxItems = array_map(function ($taxItem) {
            $taxItem['amount'] = round((float) $taxItem['amount'], 4, PHP_ROUND_HALF_UP);
            return $taxItem;
        }, $taxItems);

        return $taxItems;
    }

    private function calculateTaxAmount(string $label, float $amount, array $labels): float
    {   
        $targetTax = 0;
        $addedTaxSum = 0;
        foreach ($labels as $lbl) {
            $tax = $this->entityManager->getRepository(TaxCategory::class)->findByLabel($lbl);

            if ($lbl != $label) {
                $addedTaxSum += (float) $tax['rate'];
            } else {
                $targetTax = (float) $tax['rate'];
            }
        }

        $taxSum = $addedTaxSum + $targetTax;
        $taxRate = (100 * $taxSum) / (100 + $taxSum);
        $taxAmount = ($amount * $taxRate) / 100;

        return ($taxAmount / $taxSum) * $targetTax;
    }

    public function getValidTaxLabels(): array
    {
        $labels = [];

        $tax = $this->entityManager->getRepository(Tax::class)->findOneBy([], ['validFrom' => 'desc']);

        foreach ($tax->getTaxCategories() as $taxCategory) {
            foreach ($taxCategory->getTaxRates() as $taxRate) {
                $labels[] = $taxRate->getLabel();
            }
        }

        return $labels;
    }
}
