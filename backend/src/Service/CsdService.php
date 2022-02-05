<?php

namespace App\Service;

use App\Entity\Invoice\Invoice;
use App\Entity\Invoice\InvoiceItem;
use App\Entity\Invoice\CsdResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CsdService
{
    const SCD_ERROR_CODES = [
        'PIN_CODE_REQUIRED' => '1500'
    ];

    const SCD_PAYMENT_TYPE = [
        'Other' => '0',
        'Cash' => '1',
        'Card' => '2',
        'Check' => '3',
        'Wire Transfer' => '4',
        'Voucher' => '5',
        'Mobile Money' => '6'
    ];

    /** @var HttpClientInterface */
    protected $client;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;

    }

    public function createCsdResponse(Invoice $invoice)
    {
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return null;
            },
        ];
        $serializer = new Serializer([new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)], []);

        $normalizedResult = $serializer->normalize($invoice);

        $requestData = $this->unsetNotNeededFields($normalizedResult, ['id','invoice']);
        $requestData = json_encode($requestData);
echo $requestData; die;
        // try {        
            $response = $this->vcsdServiceRequest($requestData, 'invoices');
var_dump($response); die;
            // $csdResponse = $this->saveCsdResponse($response, $invoice);
        // } catch (\Exception $e) {
        //     return false;
        // }

        // if ($csdResponse->getMessage()) {
        //     return false;
        // }

        // return true;
    }


    private function unsetNotNeededFields(array $fullArray, array $unsetFields)
    {
        foreach ($fullArray as $key => $value) {
            if (in_array($key, $unsetFields)) {
                unset($fullArray[$key]);
            }

            if (is_array($value)) {
                if (array_keys($value) === range(0, count($value) - 1)) {
                    $fullArray[$key] = [];
                    foreach ($value as $subValue) {
                        if (!is_array($subValue)) {
                            $fullArray[$key] = $value;
                            break;
                        }
                        $fullArray[$key][] = $this->unsetNotNeededFields($subValue, $unsetFields);
                    }
                } else {
                    $fullArray[$key] = $this->unsetNotNeededFields($value, $unsetFields);
                }
            }
        }

        return $fullArray;
    }

    private function vcsdServiceRequest(string $requestPayload, string $requestAction)
    {
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $_ENV['VCSD_SERVICE_URL'] . '/' . $requestAction);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestPayload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'PAC: ' . $_ENV['VCSD_PFX_CERT_PAK']]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            // curl_setopt($ch, CURLOPT_VERBOSE, true);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSLCERT, $_ENV['VCSD_CRT_PEM_PATH']);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $_ENV['VCSD_PFX_CERT_PSWD']);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, "PEM");
            curl_setopt($ch, CURLOPT_SSLKEY, $_ENV['VCSD_KEY_PEM_PATH']);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $_ENV['VCSD_PFX_CERT_PSWD']);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, "PEM");

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch));
            }
            if (is_null(json_decode($response))) {
                throw new \Exception('Bad request');
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            curl_close($ch);
        }

        return $response;
    }

    public function checkCsdStatus()
    {
        try {        
            $response = $this->vcsdServiceRequest([]);
            $decodedResponse = json_decode($response, true);

            return isset($decodedResponse['message']);
        } catch (\Exception $e) {
            return false;
        }        
    }


    private static function format($value)
    {
        return number_format($value, 2, '.', '');
    }

    protected function saveCsdResponse($jsonData, $invoice)
    {
        $serializer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()]
        );
        $csdResponseObject = $serializer->deserialize($jsonData, CsdResponse::class, 'json');

        $csdResponseObject->setInvoice($invoice);

        $this->entityManager->persist($csdResponseObject);
        $this->entityManager->flush();

        return $csdResponseObject;
    }

    public function revertRefund(Invoice $invoice)
    {
        if (
            Invoice::TRANSACTION_TYPES[$invoice->getTransactionType()] !== 'Refund'
            || $invoice->getCsdResponseByType('Refund')
        ) {
            return;
        }

        $invoice->setStatus(Invoice::STATUS_CONFIRMED);
        $invoice->setTransactionType(array_flip(Invoice::TRANSACTION_TYPES)['Sale']);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();
    }

    public function addAdvanceAccount(int $advanceAccountId, Invoice $invoice)
    {
        $advanceAccount = $this->entityManager->getRepository(Invoice::class)->find($advanceAccountId);

        if (
            $advanceAccount
            && is_null($advanceAccount->getReference())
            && (
                is_null($advanceAccount->getCustomer()) && is_null($invoice->getCustomer())
                || ($advanceAccount->getCustomer() && $invoice->getCustomer() && $advanceAccount->getCustomer()->getId() === $invoice->getCustomer()->getId())
            )
        ) {
            $advanceAccount->setStatus(Invoice::STATUS_CANCELED);
            $advanceAccount->setReference($invoice);

            // if ($advanceAccount->getReferences()->last()->getCsdResponseByType('Refund')) {
            //     $this->entityManager->persist($advanceAccount);
            //     $this->entityManager->flush();

            //     return true;
            // }

            $this->entityManager->persist($advanceAccount);

            $refundedInvoiceItems = $this->entityManager->getRepository(InvoiceItem::class)->getRefundedInvoiceItems($advanceAccount);
            $mapRefundedInvoiceItems = self::arrayMapAssoc(function($key,$value){
                return [(int) $value['id'], (float) $value['sum_quantity']];
            }, $refundedInvoiceItems);

            $refundAdvanceAccount = clone $advanceAccount;
            $refundAdvanceAccount->setId(null);
            $refundAdvanceAccount->setPayed(false);
            $refundAdvanceAccount->setSerialNumber('RF-' . time());
            $refundAdvanceAccount->setReference($advanceAccount);
            $refundAdvanceAccount->setTransactionType(array_flip(Invoice::TRANSACTION_TYPES)['Refund']);

            $this->entityManager->persist($refundAdvanceAccount);

            $totalValue = 0;
            foreach ($advanceAccount->getInvoiceItems() as $advanceAccountArticle) {
                $refundAdvanceAccountArticle = clone $advanceAccountArticle;
                $refundAdvanceAccountArticle->setId(null);
                $refundAdvanceAccountArticle->setInvoice($refundAdvanceAccount);

                if (isset($mapRefundedInvoiceItems[(int) $advanceAccountArticle->getArticle()->getId()])) {
                    $refundAdvanceAccountArticle->setQuantity($advanceAccountArticle->getQuantity() - $mapRefundedInvoiceItems[(int) $advanceAccountArticle->getArticle()->getId()]);
                }

                $totalValue += $refundAdvanceAccountArticle->getQuantity() * $refundAdvanceAccountArticle->getPrice();

                $this->entityManager->persist($refundAdvanceAccountArticle);
            }

            $refundAdvanceAccount->setTotalValue($totalValue);
            $this->entityManager->persist($refundAdvanceAccount);

            $this->entityManager->flush();

            $response = $this->payment($refundAdvanceAccount);
            if ($response) {
                $refundAdvanceAccount->setPayed(true);
                $this->entityManager->persist($refundAdvanceAccount);
                $this->entityManager->flush();
            } else {
                $this->removeAdvanceAccount($advanceAccount->getId());
            }
            return $response;
        }

        return false;
    }

    public function removeAdvanceAccount(int $advanceAccountId)
    {
        $advanceAccount = $this->entityManager->getRepository(Invoice::class)->find($advanceAccountId);

        if (!$advanceAccount) {
            return;
        }

        $advanceAccount->setStatus(Invoice::STATUS_CONFIRMED);
        $advanceAccount->setReference(null);

        $this->entityManager->persist($advanceAccount);
        $this->entityManager->flush();
    }

    private static function arrayMapAssoc(callable $f, array $a) {
        return array_column(array_map($f, array_keys($a), $a), 1, 0);
    }

    public function cleanPlu()
    {

    }

    public function createDailyReport()
    {

    }

    public function createPeriodicReport($startDate, $endDate)
    {

    }

    public function createCrossSectionReport()
    {

    }

    protected function prepareContentFile(Invoice $invoice, $dirPathIn, $fileName = '')
    {

    }

}
