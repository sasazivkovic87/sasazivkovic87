<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User\User;
use App\Entity\Invoice\Invoice;
use App\Entity\Invoice\VcsdResponse;
use App\Entity\Invoice\EcsdResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Service\CsdService;

final class PostRespondSubscriber implements EventSubscriberInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    private $csdService;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        CsdService $csdService
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->csdService = $csdService;
    }

    /**
     * @return array|array[]
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['postRespondAction', EventPriorities::POST_RESPOND]
        ];
    }

    /**
     * @param ViewEvent $event
     */
    public function postRespondAction(ResponseEvent $event)
    {

        $method = $event->getRequest()->getMethod();
        $requestUri = $event->getRequest()->getRequestUri();
        $jsonResponse = json_decode($event->getResponse()->getContent(), true);

        if (str_contains($requestUri, '/api/invoices')) {
            if (in_array($method, [Request::METHOD_GET, Request::METHOD_POST])) {

                if (!isset($jsonResponse['modelState'])) {                
                    if (!isset($jsonResponse['id'])) {
                        $response = new JsonResponse(null, Response::HTTP_OK);
                        $event->setResponse($response);

                        return;
                    }

                    $invoice = $this->entityManager->getRepository(Invoice::class)->find($jsonResponse['id']);

                    $csdResponse = $this->csdService->getCsdResponse($invoice, EcsdResponse::class);
                    $response = new JsonResponse($csdResponse, Response::HTTP_OK);

                    $event->setResponse($response);
                }
            }
        }
    }
}
