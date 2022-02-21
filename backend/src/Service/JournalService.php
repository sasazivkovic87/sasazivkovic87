<?php

namespace App\Service;


use App\Entity\Invoice\Invoice;
use App\Entity\Invoice\InvoicePayment;
use App\Entity\Organization\Organization;
use Doctrine\ORM\EntityManagerInterface;

class JournalService
{
    CONST INVOICE_PAYMENT_LABELS = [
        0 => 'Друго безготовинско плаћање',
        1 => 'Готовина',
        2 => 'Платна картица',
        3 => 'Чек',
        4 => 'Пренос на рачун',
        5 => 'Ваучер',
        6 => 'Инстант плаћање'
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        TaxService $taxService
    )
    {
        $this->entityManager = $entityManager;
        $this->taxService = $taxService;

    }

    public function create(Invoice $invoice, \DateTime $sdcDateTime, string $invoiceCounter, string $invoiceNumber): string
    {
        $organization = $this->entityManager->getRepository(Organization::class)->findOneBy([]);

    	$invoiceType = Invoice::INVOICE_TYPES[$invoice->getInvoiceTypeId()];
    	$transactionType = Invoice::TRANSACTION_TYPES[$invoice->getTransactionTypeId()];

    	$totalAmount = 0;
        $taxItems = $this->taxService->getTaxItems($invoice);
        $totalTaxAmount = 0;

        $content = "";
        $content .= $this->getHeader($invoiceType);
        $content .= $this->createWordsRow(['ПИБ:', $organization->getCountry() . $organization->getTaxId()]);
        $content .= $this->createWordsRow(['Предузеће:', $organization->getOrganizationName()]);
        $content .= $this->createWordsRow(['Место продаје:', $organization->getOrganizationName()]);
        $content .= $this->createWordsRow(['Адреса:', $organization->getStreet()]);
        $content .= $this->createWordsRow(['Општина:', $organization->getDistrict()]);
        $content .= $this->createWordsRow(['Касир:', $invoice->getCashier() ?? '']);
        if ($invoice->getBuyerId()) {
        	$content .= $this->createWordsRow(['ИД купца:', $invoice->getBuyerId()]);
        }
        $content .= $this->createWordsRow(['ЕСИР број:', $invoice->getInvoiceNumber() ?? '']);
        $content .= $this->createWordsRow(['ЕСИР време:', $invoice->getDateAndTimeOfIssue() ? (new \DateTime($invoice->getDateAndTimeOfIssue()))->format('d.m.Y. H:i:s') : '']);
        if ($invoice->getReferentDocumentNumber()) {
        	$content .= $this->createWordsRow(['Реф. број:', $invoice->getReferentDocumentNumber()]);
        }
        if ($invoice->getReferentDocumentDT()) {
	        $content .= $this->createWordsRow(['Реф. време:', (new \DateTime($invoice->getReferentDocumentDT()))->format('d.m.Y. H:i:s')]);
	    }
        $content .= $this->getSubHeader($invoice);
    	$content .= $this->createWordsRow(['Артикли']);
        $content .= $this->getDelimeter();
    	$content .= $this->createWordsRow(['Назив', 'Цена', 'Кол.', 'Укупно']);
    	foreach ($invoice->getItems() as $item) {
    		$content .= $this->createWordsRow([$item->getName() . ' (' . implode(', ', $item->getLabels()) . ')']);
    		$content .= $this->createWordsRow([' ', self::format((float) $item->getUnitPrice()), $item->getQuantity(), ($transactionType == 'Refund' ? '-' : '') . self::format($item->getTotalAmount())]);

    		$totalAmount += $item->getTotalAmount();
    	}
        $content .= $this->getDelimeter(false);
        if ($transactionType == 'Sale') {
    		$content .= $this->createWordsRow(['Укупан износ:', self::format($totalAmount)]);
        }
        if ($transactionType == 'Refund') {
    		$content .= $this->createWordsRow(['Укупна рефундација:', self::format($totalAmount)]);
        }

        foreach ($invoice->getPayment() as $payment) {
        	$paymentLabel = self::INVOICE_PAYMENT_LABELS[$payment->getInvoicePaymentTypeId()];
    		$content .= $this->createWordsRow([$paymentLabel . ':', self::format($payment->getAmount())]);
        }
        $content .= $this->getDelimeter();

        if (!in_array($invoiceType, ['Normal', 'Advance'])) {
    		$content .= $this->createWordsRow(['ОВО НИЈЕ ФИСКАЛНИ РАЧУН'], " ", true);
        	$content .= $this->getDelimeter();
        }
    	$content .= $this->createWordsRow(['Ознака', 'Име     ', 'Стопа    ', 'Порез']);
        foreach ($taxItems as $taxItem) {
    		$content .= $this->createWordsRow([$taxItem['label'], $taxItem['categoryName'], self::format($taxItem['rate']) . '%', self::format($taxItem['amount'])]);
    		$totalTaxAmount += $taxItem['amount'];
        }
        $content .= $this->getDelimeter(false);
		$content .= $this->createWordsRow(['Укупан износ пореза:', self::format($totalTaxAmount)]);
        $content .= $this->getDelimeter();
		$content .= $this->createWordsRow(['ПФР време:', $sdcDateTime->format('d.m.Y. H:i:s')]);
		$content .= $this->createWordsRow(['ПФР број рачуна:', $invoiceNumber]);
		$content .= $this->createWordsRow(['Бројач рачуна:', $invoiceCounter]);
        $content .= $this->getDelimeter();

        $content .= $this->getFooter($invoiceType);

        return $content;
    }

    private function getHeader(string $invoiceType): string
    {
    	if (in_array($invoiceType, ['Normal', 'Advance'])) {
    		return "============ ФИСКАЛНИ РАЧУН ============\n";
    	}

    	return "======== ОВО НИЈЕ ФИСКАЛНИ РАЧУН =======\n";
    }

    private function getFooter(string $invoiceType): string
    {
    	if (in_array($invoiceType, ['Normal', 'Advance'])) {
    		return "======== КРАЈ ФИСКАЛНОГ РАЧУНА =========";
    	}

    	return "======== ОВО НИЈЕ ФИСКАЛНИ РАЧУН =======\n";
    }

    private function getDelimeter(bool $main = true): string
    {	
    	if ($main) {
    		return "========================================\n";
    	} else {
    		return "----------------------------------------\n";
    	}
    }

    private function getSubHeader(Invoice $invoice): string
    {
    	$invoiceTypeExtension = $invoice->getInvoiceTypeExtension();
    	$transactionTypeExtension = $invoice->getTransactionTypeExtension();

    	return $this->createWordsRow([$invoiceTypeExtension->full . " " . $transactionTypeExtension->full], "-", true);
    }

    private function createWordsRow(array $words, $implant = " ", bool $centered = false, int $width = 40): string
    {
    	if (count($words) == 0) {
    		return '';
    	}

    	$wordsLength = 0;
    	foreach ($words as $key => $word) {
    		$wordLength = count(str_split(utf8_decode($word), 1));
    		if ($wordLength > $width) {
    			return $this->createWordsRow(array_slice($words, 0, $key)) . $this->createWordsRow(array_values(array_merge(str_split($word, $width), array_slice($words, $key+1))));
    		}

    		$wordsLength += $wordLength;
    		if ($wordsLength > $width) {
    			return $this->createWordsRow(array_slice($words, 0, $key)) . $this->createWordsRow(array_values(array_slice($words, $key)));
    		}
    	}

    	$blancos = $width - $wordsLength;
    	if ($blancos > 0) {
	    	$numOfGaps = count($words) + ($centered ? 1 : -1);
	    	$numOfGaps = ($numOfGaps == 0) ? 1 : $numOfGaps;
	    	$gapLength = intdiv($blancos, $numOfGaps) + (($blancos % $numOfGaps) > 0 ? 1 : 0);
	    	$gaps = str_split(str_repeat($implant, $blancos), $gapLength);
    	} else {
    		$gaps = [];
    	}

    	$row = "";
    	if ($centered) {		
	    	foreach ($gaps as $key => $gap) {
	    		$row .= $gap . (isset($words[$key]) ? $words[$key] : "");
	    	}
    	} else {
	    	foreach ($words as $key => $word) {
	    		$row .= $word . (isset($gaps[$key]) ? $gaps[$key] : "");
	    	}
    	}

    	return $row . "\n";
    }

    private static function format($value, $decimal = 2): string
    {
        return number_format(round($value, $decimal, PHP_ROUND_HALF_UP), $decimal, ',', '');
    }

    public function print(Invoice $invoice): string
    {
        $journal = $invoice->getEcsdResponse()->getJournal();

        $journal = str_replace(' ', '&nbsp;', $journal);

        if (!empty($verificationQRCode = $invoice->getEcsdResponse()->getVerificationQRCode())) {
            // TODO check if width and height are correct
            $verificationQRCodeImage = '<img width="265px" src="data:image/gif;base64,' . $verificationQRCode . '" />';

            $journal = preg_replace("/(========&nbsp;КРАЈ&nbsp;ФИСКАЛНОГ&nbsp;РАЧУНА&nbsp;=========)/", "{$verificationQRCodeImage}\n======== КРАЈ&nbsp;ФИСКАЛНОГ&nbsp;РАЧУНА&nbsp;=========", $journal);

            $journal = preg_replace("/(\n========&nbsp;ОВО&nbsp;НИЈЕ&nbsp;ФИСКАЛНИ&nbsp;РАЧУН&nbsp;=======)/", "\n{$verificationQRCodeImage}\n========&nbsp;ОВО&nbsp;НИЈЕ&nbsp;ФИСКАЛНИ&nbsp;РАЧУН&nbsp;=======", $journal);
        }

        if (Invoice::INVOICE_TYPES[$invoice->getInvoiceTypeId()] == 'Copy'
            && Invoice::TRANSACTION_TYPES[$invoice->getTransactionTypeId()] == 'Refund') {
            $journal = preg_replace("/(\n========&nbsp;ОВО&nbsp;НИЈЕ&nbsp;ФИСКАЛНИ&nbsp;РАЧУН&nbsp;=======)/", "\n\nПотпис&nbsp;купца: _________________________\n========&nbsp;ОВО&nbsp;НИЈЕ&nbsp;ФИСКАЛНИ&nbsp;РАЧУН&nbsp;=======", $journal);
        }

        $journal = nl2br($journal);
        $journal = '<div style="font-family: Monospace; font-size: 12.25px; font-weight: 900">' . $journal.'</div>';

        return $journal;
    }
}

