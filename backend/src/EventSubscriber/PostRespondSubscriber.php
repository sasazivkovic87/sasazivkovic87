<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class PostRespondSubscriber implements EventSubscriberInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(
        TokenStorageInterface $tokenStorage
    )
    {
        $this->tokenStorage = $tokenStorage;
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

        // if (Request::METHOD_POST === $method) {
        //     if ($requestUri == '/api/invoices') {
        //         $response = new JsonResponse(['test' => '4w56t23'], Response::HTTP_OK);

        //         $event->setResponse($response);
        //     }
        // }
    }
}
