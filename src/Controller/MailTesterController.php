<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class MailTesterController extends AbstractController
{
    /**
     * @Route("/api/mail", name="mail_tester")
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function index(MailerInterface $mailer): JsonResponse
    {
        /*$email = (new Email())
            ->from('simonpankovski@gmail.com')
            ->to('simonp9999@gmail.com')
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            ->replyTo('simonpankovski@gmail.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Time for Symfony Mailer!')
            ->text('Sending emails is fun again!')
            ->html('<p>See Twig integration for better HTML integration!</p>');
        $mailer->send($email);*/
        return $this->json("Sent");
    }
}
