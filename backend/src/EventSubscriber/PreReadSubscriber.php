<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User\User;
use App\Entity\Invoice\Invoice;
use App\Entity\Warehouse\Warehouse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Service\CsdService;

final class PreReadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        TokenStorageInterface $tokenStorage,
        CsdService $csdService
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->csdService = $csdService;
    }

    /**
     * @return array|array[]
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['preReadAction', EventPriorities::PRE_READ]
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function preReadAction(RequestEvent $event)
    {
        $user = ($token = $this->tokenStorage->getToken()) ? $token->getUser() : null;

        $method = $event->getRequest()->getMethod();
        $requestUri = $event->getRequest()->getRequestUri();

        if (Request::METHOD_POST === $method) {
            if ($requestUri == '/api/invoices') {
                $jsonRequest = json_decode($event->getRequest()->getContent(), true);

                $errors = $this->csdService->validateInvoice($jsonRequest);

                if (count($errors) > 0) {
                    $response = [
                        'message' => 'The request is invalid.',
                        'modelState' => $errors
                    ];
                    $event->setResponse(new JsonResponse($response, Response::HTTP_OK));
                }
            }
        }
    }
}
