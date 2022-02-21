<?php

namespace App\Service;

use App\Entity\Organization\Organization;
use App\Entity\Tax\Tax;
use App\Entity\Tax\TaxCategory;
use App\Entity\Invoice\Invoice;
use App\Entity\Invoice\InvoiceItem;
use App\Entity\Invoice\InvoicePayment;
use App\Entity\Invoice\VcsdResponse;
use App\Entity\Invoice\EcsdResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Messenger\MessagePublish\EcsdMessage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class CsdService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        MessageBusInterface $bus,
        TaxService $taxService,
        QRCodeService $QRCodeService,
        CryptService $cryptService,
        JournalService $journalService,
        ValidatorInterface $validator
    )
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->bus = $bus;
        $this->taxService = $taxService;
        $this->QRCodeService = $QRCodeService;
        $this->cryptService = $cryptService;
        $this->journalService = $journalService;
        $this->validator = $validator;

    }

    public function createCsdResponse(Invoice $invoice): void
    {
        $ecsdResponse = $this->createEcsdResponse($invoice);

        if ($ecsdResponse) {
            $this->bus->dispatch(new EcsdMessage($invoice->getId()));
        }
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

        $invoiceId = $this->entityManager->getRepository(Invoice::class)->getInvoiceId($invoice->getInvoiceNumber());

        $counter = $this->entityManager->getRepository(Invoice::class)->getTypeCounter($invoice->getInvoiceNumber(), $invoice->getTransactionTypeId(), $invoice->getInvoiceTypeId());
        $invoiceCounter = $counter . '/' . $invoiceId . $invoiceExtension->short . $transactionExtension->short;
        $invoiceNumber = $serialNumber . '-' . $serialNumber . '-' . $invoiceId;

        $totalAmount = $this->entityManager->getRepository(Invoice::class)->getTotalAmount($invoice);

        $taxItems = $this->taxService->getTaxItems($invoice);

        $urlParams = json_encode([$_ENV['ECSD_SERIAL_NUMBER'], $invoice->getInvoiceNumber(), $invoiceId]);
        $urlSignature = $this->cryptService->hashHmacMd5($urlParams, $_ENV['APP_SECRET']);
        $verificationUrl = $_ENV['ECSD_VERIFICATION_URL'] . '/' . urlencode(base64_encode($urlParams)) . '/' . urlencode($urlSignature);

        $this->cryptService->hashHmacMd5(json_encode([$_ENV['ECSD_SERIAL_NUMBER'], $invoiceId]), $_ENV['APP_SECRET']);

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

        $response['signature'] = $this->cryptService->hashHmacMd5(json_encode($response), $_ENV['APP_SECRET'], true);
        $response['encryptedInternalData'] = $this->cryptService->encrypt(json_encode($response), $_ENV['APP_SECRET'], true);

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

        $requestData = $this->unsetNotNeededFields($normalizedResult, ['id','invoice', 'ecsdResponse', 'vcsdResponse', 'invoiceTypeExtension', 'transactionTypeExtension', 'copied', '__initializer__', '__cloner__', '__isInitialized__']);

        $requestData['invoiceNumber'] = $_ENV['ESIR_NUMBER'];
        $requestData = json_encode($requestData);

        try {        
            $response = $this->vcsdServiceRequest($requestData, 'invoices');
            $this->saveCsdResponse($response, $invoice, VcsdResponse::class);
        } catch (\Exception $e) {
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

        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return null;
            },
        ];
        $serializer = new Serializer([new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)], []);

        $normalizedResult = $serializer->normalize($csdResponse);

        if (isset($normalizedResult['message'])) {
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

    public function validateInvoice($invoiceRequest): array
    {
        $errorResults = [];
        
        $notBlank = new \Symfony\Component\Validator\Constraints\NotBlank(['message' => '2800']);
        $length255 = new \Symfony\Component\Validator\Constraints\Length(['max' => 255, 'maxMessage' => '2803']);
        $digit = new \Symfony\Component\Validator\Constraints\Type(['type' => ['float', 'integer'], 'message' => '2805']);
        $array = new \Symfony\Component\Validator\Constraints\Type(['type' => ['array'], 'message' => '2805']);
        $inArrayInvoiceType = new \App\Validator\InArray(['checkArray' => array_merge(Invoice::INVOICE_TYPES, array_map(function ($item) { return (string) $item; }, array_flip(Invoice::INVOICE_TYPES))), 'message' => '2805']);
        $inArrayTransactionType = new \App\Validator\InArray(['checkArray' => array_merge(Invoice::TRANSACTION_TYPES, array_map(function ($item) { return (string) $item; }, array_flip(Invoice::TRANSACTION_TYPES))), 'message' => '2805']);
        $inArrayOptions = new \App\Validator\InArray(['checkArray' => ["", "0", "1"], 'message' => '2805']);
        $isDateTime = new \App\Validator\IsDateTime(['message' => '2805']);
        $dbExists = new \App\Validator\DbExists(['class' => EcsdResponse::class, 'field' => 'invoiceNumber', 'message' => '2805']);
        $inArrayLabels = new \App\Validator\InArray(['checkArray' => $this->taxService->getValidTaxLabels(), 'message' => '2805']);
        $inArrayPaymentType = new \App\Validator\InArray(['checkArray' => array_merge(InvoicePayment::INVOICE_PAYMENTS, array_map(function ($item) { return (string) $item; }, array_flip(InvoicePayment::INVOICE_PAYMENTS))), 'message' => '2805']);

        $referentDocumentNumberValidation = in_array(($invoiceRequest['transactionType'] ?? ''),['1', 'Refund']) || in_array(($invoiceRequest['invoiceType'] ?? ''),['2', 'Copy']) ? [$notBlank, $length255, $dbExists] : [$length255, $dbExists];

        $fields = [
            [
                'property' => 'dateAndTimeOfIssue',
                'value' => $invoiceRequest['dateAndTimeOfIssue'] ?? '',
                'constraints' => [$notBlank, $length255, $isDateTime]
            ],
            [
                'property' => 'cashier',
                'value' => $invoiceRequest['cashier'] ?? '',
                'constraints' => [$length255]
            ],
            [
                'property' => 'buyerId',
                'value' => $invoiceRequest['buyerId'] ?? '',
                'constraints' => [$length255]
            ],
            [
                'property' => 'buyerCostCenterId',
                'value' => $invoiceRequest['buyerCostCenterId'] ?? '',
                'constraints' => [$length255]
            ],
            [
                'property' => 'invoiceType',
                'value' => $invoiceRequest['invoiceType'] ?? '',
                'constraints' => [$notBlank, $inArrayInvoiceType]
            ],
            [
                'property' => 'transactionType',
                'value' => $invoiceRequest['transactionType'] ?? '',
                'constraints' => [$notBlank, $inArrayTransactionType]
            ],
            [
                'property' => 'invoiceNumber',
                'value' => $invoiceRequest['invoiceNumber'] ?? '',
                'constraints' => [$length255]
            ],
            [
                'property' => 'referentDocumentNumber',
                'value' => $invoiceRequest['referentDocumentNumber'] ?? '',
                'constraints' => $referentDocumentNumberValidation
            ],
            [
                'property' => 'referentDocumentDT',
                'value' => $invoiceRequest['referentDocumentDT'] ?? '',
                'constraints' => [$length255, $isDateTime]
            ],
            [
                'property' => 'options.omitQRCodeGen',
                'value' => $invoiceRequest['options']['omitQRCodeGen'] ?? '',
                'constraints' => [$length255, $inArrayOptions]
            ],
            [
                'property' => 'options.omitTextualRepresentation',
                'value' => $invoiceRequest['options']['omitTextualRepresentation'] ?? '',
                'constraints' => [$length255, $inArrayOptions]
            ],
            [
                'property' => 'items',
                'value' => $invoiceRequest['items'] ?? '',
                'constraints' => [$array]
            ],
            [
                'property' => 'payment',
                'value' => $invoiceRequest['payment'] ?? '',
                'constraints' => [$array]
            ]
        ];

        foreach (($invoiceRequest['items'] ?? []) as $key => $invoiceItem) {
            $fields[] = [
                'property' => 'items[' . $key . '].name',
                'value' => $invoiceItem['name'] ?? '',
                'constraints' => [$notBlank, $length255]
            ];
            $fields[] = [
                'property' => 'items[' . $key . '].quantity',
                'value' => floatval($invoiceItem['quantity'] ?? ''),
                'constraints' => [$notBlank, $length255, $digit]
            ];
            $fields[] = [
                'property' => 'items[' . $key . '].unitPrice',
                'value' => floatval($invoiceItem['unitPrice'] ?? ''),
                'constraints' => [$notBlank, $length255, $digit]
            ];
            $fields[] = [
                'property' => 'items[' . $key . '].gtin',
                'value' => $invoiceItem['gtin'] ?? '',
                'constraints' => [$length255]
            ];
            $fields[] = [
                'property' => 'items[' . $key . '].totalAmount',
                'value' => floatval($invoiceItem['totalAmount'] ?? ''),
                'constraints' => [$notBlank, $length255, $digit]
            ];

            foreach ($invoiceItem['labels'] as $labelKey => $label) {
                $fields[] = [
                    'property' => 'items[' . $key . '].labels[' . $labelKey . ']',
                    'value' => $label ?? '',
                    'constraints' => [$notBlank, $inArrayLabels]
                ];
            }
        }

        foreach (($invoiceRequest['payment'] ?? []) as $key => $invoicePayment) {
            $fields[] = [
                'property' => 'payment[' . $key . '].paymentType',
                'value' => $invoicePayment['paymentType'] ?? '',
                'constraints' => [$notBlank, $length255, $inArrayPaymentType]
            ];
            $fields[] = [
                'property' => 'payment[' . $key . '].amount',
                'value' => floatval($invoicePayment['amount'] ?? ''),
                'constraints' => [$notBlank, $length255, $digit]
            ];
        }

        foreach ($fields as $field) {
            $errors = $this->validator->validate($field['value'], $field['constraints']);

            if (count($errors) > 0) {
                $errorResult = ['property' => $field['property']];

                foreach ($errors as $error) {
                    $errorResult['errors'][] = $error->getMessage();
                }

                $errorResults[] = $errorResult;
            }
        }

        return $errorResults;
    }

    public function export($unreadInvoices, $dirPath)
    {
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return null;
            },
        ];
        $serializer = new Serializer([new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)], [new JsonEncoder()]);
        $fileSystem = new Filesystem();

        foreach ($unreadInvoices as $invoice) {
            $normalizedResult = $this->unsetNotNeededFields($serializer->normalize($invoice), ['id','invoice', 'ecsdResponse', 'vcsdResponse', 'invoiceTypeExtension', 'transactionTypeExtension', 'copied', '__initializer__', '__cloner__', '__isInitialized__']);

            $jsonContent = $serializer->serialize($normalizedResult, 'json');
            
            try {
                // $fileSystem->chmod($dirPath, 0777, 0000, true);
                $fileSystem->mkdir($dirPath . '/' . $invoice->getEcsdResponse()->getRequestedBy());

                $filePath = $dirPath . '/' . $invoice->getEcsdResponse()->getRequestedBy() . '/' . $invoice->getEcsdResponse()->getInvoiceNumber() . '.json';
                $fileSystem->touch($filePath);
                $fileSystem->appendToFile($filePath, $jsonContent);

                $invoice->setCopied(true);
                $this->entityManager->persist($invoice);
            }
            catch(IOExceptionInterface $e) {
                throw new \Exception("Bad Request");
            }
        }

        $this->entityManager->flush();
    }
}
