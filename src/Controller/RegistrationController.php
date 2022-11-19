<?php

namespace App\Controller;

use App\{Entity\User, Security\EmailVerifier};
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{HttpFoundation\Request,
    HttpFoundation\Response,
    Mailer\MailerInterface,
    Mime\Address,
    Validator\Validator\ValidatorInterface};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route ("/api", name="auth_controller")
 */
class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register", methods={"POST"})
     */
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $JWTManager,
        ValidatorInterface $validator
    ): Response {
        $formData = json_decode($request->getContent(), true);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $formData['username']]);
        if (!($user==null)) {
            return $this->json(['code' => 400, 'message' => 'User Already exists!']);
        }
        $user = new User();
        $password = $formData['password'];
        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $password
            )
        )->setEmail($formData['username']);

        $errors = $validator->validate($user);
        if (sizeof($errors) > 0 || strlen($password) < 8 || strlen($password) > 50){
            return $this->json(["message" => "Invalid data provided!", "code"=> 400]);
        }
        $entityManager->persist($user);
        $entityManager->flush();

        $token = $JWTManager->create($user);
        return $this->json(["token" => $token, "code" => 200]);
    }
}
