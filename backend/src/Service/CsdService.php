<?php

namespace App\Service;

use App\Entity\Organization\Organization;
use App\Entity\Tax\TaxCategory;
use App\Entity\Invoice\Invoice;
use App\Entity\Invoice\InvoiceItem;
use App\Entity\Invoice\VcsdResponse;
use App\Entity\Invoice\EcsdResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CsdService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        TaxService $taxService,
        QRCodeService $QRCodeService,
        CryptService $cryptService,
        JournalService $journalService
    )
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->taxService = $taxService;
        $this->QRCodeService = $QRCodeService;
        $this->cryptService = $cryptService;
        $this->journalService = $journalService;

    }

    public function createCsdResponse(Invoice $invoice): void
    {
        $ecsdResponse = $this->createEcsdResponse($invoice);

        // if ($ecsdResponse) {
        //     $this->createVcsdResponse($invoice);
        // }
    }

    public function createEcsdResponse(Invoice $invoice)
    {
        $organization = $this->entityManager->getRepository(Organization::class)->findOneBy([]);

        $serialNumber = $organization->getSerialNumber();

        $sdcDateTime = new \DateTime();
        $sdcDateTime->setTimezone(new \DateTimeZone($organization->getServerTimeZone()));

        $invoiceExtension = $invoice->getInvoiceTypeExtension();
        $transactionExtension = $invoice->getTransactionTypeExtension();

        if (!$invoiceExtension || !$transactionExtension) {
            return false;
        }

        $invoiceId = $invoice->getId();

        $counter = $this->entityManager->getRepository(Invoice::class)->getCountOfObjects($invoice->getInvoiceTypeId(), $invoice->getTransactionTypeId());
        $invoiceCounter = $counter . '/' . $invoiceId . $invoiceExtension->short . $transactionExtension->short;
        $invoiceNumber = $serialNumber . '-' . $serialNumber . '-' . $invoiceId;

        $totalAmount = $this->entityManager->getRepository(Invoice::class)->getTotalAmount($invoice);

        $taxItems = $this->taxService->getTaxItems($invoice);

        $verificationUrl = 'https://www.aktiv.rs';
        $verificationQRCode = $this->QRCodeService->generate($verificationUrl);

        $journal = $this->journalService->create($invoice, $sdcDateTime, $invoiceCounter, $invoiceNumber);

        $response = [
            "requestedBy" => $serialNumber,
            "sdcDateTime" => $sdcDateTime->format('c'),
            "invoiceCounter" => $invoiceCounter,
            "invoiceCounterExtension" => $invoiceExtension->short . $transactionExtension->short,
            "invoiceNumber" => $invoiceNumber,
            "taxItems" => array_values($taxItems),
            "verificationUrl" => $verificationUrl,
            "verificationQRCode" => $verificationQRCode,
            "journal" => $journal,
            "messages" => "Success",
            "signedBy" => $serialNumber,
            "encryptedInternalData" => "czilWoiAnRjP8",
            "totalCounter" => $invoiceId,
            "transactionTypeCounter" => $counter,
            "totalAmount" => self::format($totalAmount),
            "taxGroupRevision" => 5,
            "businessName" => $organization->getOrganizationName(),
            "tin" => $organization->getCountry() . $organization->getTaxId(),
            "locationName" => $organization->getOrganizationName(),
            "address" => $organization->getStreet(),
            "district" => $organization->getDistrict(),
            "mrc" => $_ENV['ECSD_MAKE_CODE'] . '-' . $_ENV['ECSD_SOFTWARE_VERSION_CODE'] . '-' . $serialNumber
        ];

        $response['encryptedInternalData'] = base64_encode($this->cryptService->encrypt(json_encode($response)));
        $response['signature'] = base64_encode(hash_hmac('md5', json_encode($response), $_ENV['APP_SECRET']));

        $this->saveCsdResponse(json_encode($response), $invoice, EcsdResponse::class);

        return true;
    }

    public function createVcsdResponse(Invoice $invoice): bool
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

        try {        
            $response = $this->vcsdServiceRequest($requestData, 'invoices');
            $csdResponse = $this->saveCsdResponse($response, $invoice, VcsdResponse::class);
        } catch (\Exception $e) {
            return false;
        }

        if ($csdResponse->getMessage()) {
            return false;
        }

        return true;
    }


    private function vcsdServiceRequest(string $requestPayload, string $requestAction): ?string
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

    protected function saveCsdResponse(string $jsonData, Invoice $invoice, string $csdResponseClass)
    {
        $serializer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()]
        );
        $csdResponseObject = $serializer->deserialize($jsonData, $csdResponseClass, 'json');

        $csdResponseObject->setInvoice($invoice);

        $this->entityManager->persist($csdResponseObject);
        $this->entityManager->flush();

        return $csdResponseObject;
    }

    public function getCsdResponse(Invoice $invoice, string $csdResponseClass): ?array
    {
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return null;
            },
        ];
        $serializer = new Serializer([new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)], []);

        $csdResponse = null;
        $this->entityManager->refresh($invoice);
        if ($csdResponseClass === VcsdResponse::class) {
            $csdResponse = $invoice->getVcsdResponse();
        }
        else if ($csdResponseClass === EcsdResponse::class) {
            $csdResponse = $invoice->getEcsdResponse();
        }
        if (!$csdResponse) {
            return null;
        }

        $normalizedResult = $serializer->normalize($csdResponse);

        if (isset($notNeededFields['message'])) {
            return ['modelState' => $normalizedResult['modelState'], 'message' => $normalizedResult['message']];
        }

        return $this->unsetNotNeededFields($normalizedResult, ['id', 'invoice', 'modelState', 'message']);
    }

    private static function format($value, $decimal = 4): float
    {
        return round($value, $decimal, PHP_ROUND_HALF_UP);
        // return number_format(round($value, $decimal, PHP_ROUND_HALF_UP), $decimal, '.', '');
    }

    private function unsetNotNeededFields(array $fullArray, array $unsetFields): array
    {
        foreach ($fullArray as $key => $value) {
            if (in_array($key, $unsetFields)) {
                unset($fullArray[$key]);
                continue;
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
}
