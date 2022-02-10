<?php

namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\User\User;
use App\Entity\Invoice\Invoice;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Service\CsdService;

final class PostWriteSubscriber implements EventSubscriberInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

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
            KernelEvents::VIEW => ['postWriteAction', EventPriorities::POST_WRITE]
        ];
    }

    /**
     * @param ViewEvent $event
     */
    public function postWriteAction(ViewEvent $event)
    {
        $object = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        $user = ($token = $this->tokenStorage->getToken()) ? $token->getUser() : null;
        // All POST requests
        if (Request::METHOD_POST === $method) {
            if ($object instanceof Invoice) {
                $this->csdService->createCsdResponse($object);
            }
        }

    }
}
