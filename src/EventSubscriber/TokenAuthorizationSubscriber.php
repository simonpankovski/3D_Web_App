<?php

namespace App\EventSubscriber;

use App\Controller\HasAdminRole;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\{Event\ControllerEvent,
    Event\ExceptionEvent,
    Exception\AccessDeniedHttpException,
    KernelEvents};

class TokenAuthorizationSubscriber implements EventSubscriberInterface
{
    private $tokenManager;

    public function __construct(JWTTokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function onRequest(ControllerEvent $event)
    {
        $controller = $event->getController();

        // when a controller class defines multiple action methods, the controller
        // is returned as [$controllerInstance, 'methodName']
        if (is_array($controller)) {
            $controller = $controller[0];
        }
        if ($controller instanceof HasAdminRole) {
            $token = preg_split("/ /", $event->getRequest()->headers->get("authorization"))[1];
            $decodedToken = $this->tokenManager->parse($token);
            if (!in_array("ROLE_ADMIN", $decodedToken["roles"])) {
                throw new AccessDeniedHttpException('This action needs a valid token!');
            }
        }
    }

    public function processException(ExceptionEvent $event)
    {
        if ($event->getThrowable() instanceof AccessDeniedHttpException) {
            $event->setResponse(
                new JsonResponse(['code' => 403, 'message' => 'Access denied, insufficient permissions!'])
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.controller' => 'onRequest',
            KernelEvents::EXCEPTION => 'processException',
        ];
    }
}
