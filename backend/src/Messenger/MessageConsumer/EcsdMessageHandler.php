<?php

namespace App\Messenger\MessageConsumer;

use App\Entity\Invoice\Invoice;
use App\Entity\Invoice\EcsdResponse;
use App\Entity\Invoice\VcsdResponse;
use App\Messenger\MessagePublish\EcsdMessage;
use App\Messenger\MessagePublish\VerifiedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use App\Service\CsdService;

class EcsdMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        MessageBusInterface $bus,
        EntityManagerInterface $entityManager,
        CsdService $csdService,
        LoggerInterface $messengerLogger
    ) {
        $this->bus = $bus;
        $this->entityManager = $entityManager;
        $this->csdService = $csdService;
        $this->logger = $messengerLogger;
    }

    /**
     * Invoke.
     */
    public function __invoke(EcsdMessage $ecsdMessage)
    {
        $invoice = $this->entityManager->getRepository(Invoice::class)->findOneBy(['id' => $ecsdMessage->getInvoiceId()]);

        $response = $this->csdService->createVcsdResponse($invoice);

        if (!$response) {
            $this->bus->dispatch(new EcsdMessage($ecsdMessage->getInvoiceId()), [new DelayStamp(60000)]);
        } else {

            $ecsdResponse = $this->csdService->getCsdResponse($invoice, EcsdResponse::class);
            $vcsdResponse = $this->csdService->getCsdResponse($invoice, VcsdResponse::class);

            unset($ecsdResponse['encryptedInternalData']);
            $ecsdResponse['vcsdVerificationUrl'] = $vcsdResponse['verificationUrl'] ?? null;
            $ecsdResponseJson = base64_encode(json_encode($ecsdResponse));

            $this->bus->dispatch(new VerifiedMessage($ecsdResponseJson));
        }

        $this->logger->info("Ecsd Message successfully handled.");
    }
}