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
        if(!is_dir($commonPath)){
            mkdir(getcwd() . "\\models");
        }
        $folderNames = array_slice(scandir($commonPath), 2);
        if (count($folderNames) > 0) {
            foreach ($folderNames as $folderName) {
                $fileNames = array_slice(scandir($commonPath . "\\" . $folderName), 2);
                foreach ($fileNames as $fileName) {
                    unlink($commonPath . "\\" . $folderName . "\\" . $fileName);
                }
                rmdir($commonPath . "\\" . $folderName);
            }
        }
    }
    public function terminateEvent(TerminateEvent $event)
    {
        if (str_contains($event->getRequest()->getUri(), "/api/texture/")) {
            $commonPath = getcwd() . "\\textures\\";
            $this->deleteFilesInFolder($commonPath);
        }
        elseif (str_contains($event->getRequest()->getUri(), "/api/model/")) {
            $commonPath = getcwd() . "\\models\\";
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