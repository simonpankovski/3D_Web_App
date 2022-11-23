<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;


class PostResponseSubscriber implements EventSubscriberInterface
{
    private $tokenManager;

    public function __construct(JWTTokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function deleteFilesInFolder(string $commonPath): void
    {
    }
    public function terminateEvent(TerminateEvent $event)
    {
        if (str_contains($event->getRequest()->getUri(), "/api/texture/")) {
            $commonPath = getcwd() . "/textures/";
            $this->deleteFilesInFolder($commonPath);

        }
        elseif (str_contains($event->getRequest()->getUri(), "/api/model/")) {
            $commonPath = getcwd() . "/models/";
            $this->deleteFilesInFolder($commonPath);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.terminate' => 'terminateEvent'
        ];
    }
}