<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class PostResponseSubscriber implements EventSubscriberInterface
{
    private $tokenManager;

    public function __construct(JWTTokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function postResponse(ResponseEvent $event)
    {
        if (str_contains($event->getRequest()->getUri(), "/api/texture/")) {
            $commonPath = getcwd() . "\\models\\textures\\";
            $folderNames = array_slice(scandir($commonPath), 2);
            foreach ($folderNames as $folderName) {
                $fileNames = array_slice(scandir($commonPath . "\\" . $folderName), 2);
                foreach ($fileNames as $fileName) {
                    unlink($commonPath . "\\" . $folderName . "\\" .$fileName);
                }
            }
        }
    }

    public function processException(ExceptionEvent $event)
    {
        if ($event->getThrowable() instanceof AccessDeniedHttpException) {
            $event->setResponse(
                new JsonResponse(['code' => 403, 'message' => $event->getThrowable()->getMessage()])
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.response' => 'postResponse',
            KernelEvents::EXCEPTION => 'processException',
        ];
    }
}