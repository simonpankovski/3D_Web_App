<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\Util\VerifyEmailQueryUtility;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

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
        MailerInterface $mailer
    ): Response {
        $user = new User();
        $formData = json_decode($request->getContent(), true);
        dd($formData);
        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $formData['password']
            )
        )->setIsVerified(false)->setRoles(['USER_ROLE'])->setEmail($formData['username']);
        dd($user);
        $entityManager->persist($user);
        $entityManager->flush();

        // generate a signed url and email it to the user
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
        return $this->json($token);
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
        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        //$this->addFlash('success', 'Your email address has been verified.');

        return $this->json('succ');
    }
}
