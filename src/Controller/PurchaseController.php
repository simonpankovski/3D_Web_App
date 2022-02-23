<?php

namespace App\Controller;

use App\DTO\PurchaseDTO;
use App\Entity\{Model, Purchase, Texture, TexturePurchase, User};
use Doctrine\ORM\{NonUniqueResultException, NoResultException};
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{HttpFoundation\Request, HttpFoundation\Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/purchase")
 */
class PurchaseController extends AbstractController
{
    public function getUserFromToken(Request $request, JWTTokenManagerInterface $tokenManager)
    {
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $tokenManager->parse($token);
        $ownerEmail = $decodedToken["username"];
        return $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $ownerEmail]);
    }

    /**
     * @Route("/", methods={"GET"})
     */
    public function getPurchases(Request $request, JWTTokenManagerInterface $tokenManager)
    {
        $user = $this->getUserFromToken($request, $tokenManager);
        if (!$user) {
            return $this->json(['code' => 403, 'message' => 'Not Authorized!'], 403);
        }
        $em = $this->getDoctrine();
        $texturePurchases = $em->getRepository(TexturePurchase::class)->findBy(['user' => $user->getId()]);
        $modelPurchases = $em->getRepository(Purchase::class)->findBy(['user' => $user->getId()]);
        $purchases = [];
        if (count($texturePurchases) > 0) {
            foreach ($texturePurchases as $texturePurchase) {
                $texture = $this->getDoctrine()->getRepository(Texture::class)->findOneBy(
                    ['id' => $texturePurchase->getTexture()]
                );
                $purchases[] = new PurchaseDTO($texture->getName(), "texture", $texture->getPrice(), $texture->getId());
            }
        }
        if (count($modelPurchases) > 0) {
            foreach ($modelPurchases as $modelPurchase) {
                $model = $this->getDoctrine()->getRepository(Model::class)->findOneBy(
                    ['id' => $modelPurchase->getModel()]
                );
                $purchases[] = new PurchaseDTO($model->getName(), "model", $model->getPrice(), $model->getId());
            }
        }
        return $this->json(['code' => 200, 'message' => $purchases]);
    }

    /**
     * @Route("/", methods={"POST"})
     */
    public function index(Request $request, JWTTokenManagerInterface $tokenManager): Response
    {
        $queryParams = $request->query->all();
        $em = $this->getDoctrine();
        $model = $em->getRepository(Model::class)->findOneBy(['id' => $queryParams['model']]);
        if (!$model) {
            return $this->json(['code' => 404, 'message' => 'Model wasn\'t found']);
        }
        $user = $this->getUserFromToken($request, $tokenManager);
        if (!$user) {
            return $this->json(['code' => 403, 'message' => 'Not Authorized!'], 403);
        }
        $purchase = new Purchase($user, $model);
        $manager = $em->getManager();
        try {
            $manager->persist($purchase);
            $manager->flush();
        } catch (\Exception $exception) {
            if (str_contains($exception, "Unique violation")) {
                return $this->json(['code' => 409, 'message' => 'Already purchased']);
            }
        }
        return $this->json('success');
    }

    /**
     * @Route("/{id}", methods={"POST"})
     */
    public function addRating(
        ?Model $model = null,
        Request $request,
        int $id,
        ValidatorInterface $validator,
        JWTTokenManagerInterface $tokenManager
    ): Response {
        if (!$model) {
            return $this->json(['code' => 404, 'message' => 'Model wasn\'t found']);
        }
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $tokenManager->parse($token);
        $ownerEmail = $decodedToken["username"];
        $rating = $request->query->get("rating");

        $doctrine = $this->getDoctrine();
        $user = $doctrine->getRepository(User::class)->findOneBy(['email' => $ownerEmail]);
        $purchase = $doctrine->getRepository(Purchase::class)->findOneBy(['user' => $user->getId(), 'model' => $id]);
        $purchase->setRating($rating);
        $errors = $validator->validate($purchase);
        if (count($errors) > 0) {
            return $this->json(['code' => 400, 'message' => 'Invalid rating, allowed values are between 1 and 5']);
        }
        $this->getDoctrine()->getManager()->flush();
        return $this->json("success");
    }
}