<?php

namespace App\Service;

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
        TokenStorageInterface $tokenStorage
    )
    {
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;

    }

    public function createCsdResponse(Invoice $invoice)
    {
        $ecsdResponse = $this->createEcsdResponse($invoice);

        if ($ecsdResponse) {
            $this->createVcsdResponse($invoice);
        }

        return $ecsdResponse;
    }

    public function createEcsdResponse(Invoice $invoice)
    {
        return true;
    }

    public function createVcsdResponse(Invoice $invoice)
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

    public function getCsdResponse(Invoice $invoice, string $csdResponseClass)
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
            $fieldNames = $this->entityManager->getClassMetadata(Invoice::class)->getFieldNames();

            return ['modelState' => $normalizedResult['modelState'], 'message' => $normalizedResult['message']];
        }

        return $this->unsetNotNeededFields($normalizedResult, ['id', 'invoice', 'modelState', 'message']);
    }

    private function unsetNotNeededFields(array $fullArray, array $unsetFields)
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
