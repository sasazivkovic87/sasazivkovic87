<?php

namespace App\Messenger\MessageConsumer;

use App\Entity\InvoiceResponse;
use App\Messenger\MessagePublish\VerifiedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class VerifiedMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $messengerLogger
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $messengerLogger;
    }

    /**
     * Invoke.
     */
    public function __invoke(VerifiedMessage $verifiedMessage)
    {
        $ecsdResponseJson = base64_decode($verifiedMessage->getEcsdResponseJson());
        $ecsdResponseJsonDecoded = json_decode($ecsdResponseJson, true);

        $invoiceResponse = new InvoiceResponse();
        $invoiceResponse->setJsonResponse($ecsdResponseJson);
        $invoiceResponse->setEcsdVerificationUrl($ecsdResponseJsonDecoded['verificationUrl']);
        $invoiceResponse->setVcsdVerificationUrl($ecsdResponseJsonDecoded['vcsdVerificationUrl'] ?? null);

        $this->entityManager->persist($invoiceResponse);
        $this->entityManager->flush();

        $this->logger->info("Verify Message successfully handled.");
    }
}