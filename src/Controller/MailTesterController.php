<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Google\Cloud\Storage\StorageClient;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class MailTesterController extends AbstractController
{
    /**
     * @Route("/api/mail", name="mail_tester")
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function index(Request $request, MailerInterface $mailer, UserRepository $userRepository, JWTTokenManagerInterface $JWTManager): JsonResponse
    {

        $decodedJson = json_decode(file_get_contents(realpath("../config/json_credentials/savvy-octagon-334317-81205c560b3e.json")), true);
        //$token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket($_ENV['BUCKET_NAME']);
        $object = $bucket->object('57-couch-obj.rar');
        $object->downloadToFile('57-couch-obj.rar');

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
