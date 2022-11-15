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
    private $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * @Route("/register", name="app_register", methods={"POST"})
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $JWTManager,
        MailerInterface $mailer,
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
        $email = (new TemplatedEmail())
            ->from(new Address('simonpankovski@gmail.com', '3D Web App Bot'))
            ->to($user->getEmail())
            ->subject('Please Confirm your Email')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                          'token' => $token
                      ]);
        $mailer->send($email);
        return $this->json(["token" => $token, "code" => 200]);
    }

    /**
     * @Route("/verify", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request, JWTTokenManagerInterface $manager): Response
    {
        // validate email confirmation link, sets User::isVerified=true and persists
        $decodedToken = ($request->query->has("token")) ? $manager->parse($request->get("token")) : null;
        if ($decodedToken === null) {
            return $this->json("The token query parameter is required");
        }
        $expirationTime = date('Y-m-d H:i:s', $decodedToken['exp'] + 3600);
        if (date('Y-m-d H:i:s', strtotime('1 hour')) > $expirationTime) {
            return $this->json("Expired token, please request a new email validation link!");
        }
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $decodedToken["username"]]);
        $em = $this->getDoctrine()->getManager();
        if ($user->isVerified() === true) {
            return $this->json("The user has already been verified!");
        }
        $user->setIsVerified(true);
        $em->flush();

        return $this->redirect('http://localhost:8080');
    }
}
