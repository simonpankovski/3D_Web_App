<?php

namespace App\Controller;

use App\Entity\{Model, Purchase, User};
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
    /**
     * @Route("/", methods={"POST"})
     */
    public function index(Request $request): Response
    {
        $queryParams = $request->query->all();
        $em = $this->getDoctrine();
        $user = $em->getRepository(User::class)->findOneBy(['email' => $queryParams['user']]);
        $model = $em->getRepository(Model::class)->findOneBy(['id' => $queryParams['model']]);
        if(!$user || !$model){
            return $this->json(['code' => 404, 'message' => 'Entity not found!']);
        }
        $purchase = new Purchase($user, $model);
        $manager = $em->getManager();
        try {
            $manager->persist($purchase);
            $manager->flush();
        } catch (\Exception $exception){
            if (str_contains($exception, "Unique violation")){
                return $this->json(['code' => 409, 'message' => 'Already purchased']);
            }
        }
        return $this->json('success');
    }

    /**
     * @Route("/{id}", methods={"POST"})
     */
    public function addRating(?Model $model = null, Request $request, int $id, ValidatorInterface $validator, JWTTokenManagerInterface $tokenManager): Response
    {
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

    /**
     * @Route("/{id}", methods={"GET"})
     */
    public function getRating(?Model $model = null, int $id)
    {
        if (!$model) {
            return $this->json(['code' => 404, 'message' => 'Model wasn\'t found']);
        }
        $query = $this->getDoctrine()->getRepository(Purchase::class);
        try {
            $count = $query->createQueryBuilder('p')
                ->select('avg(p.rating)')
                ->where('p.model = :id')
                ->setParameter('id', $id)
                ->getQuery()->getSingleScalarResult();
            return $this->json(['code' => 200, 'message' => $count]);
        } catch (NoResultException $e) {
            return $this->json(['code' => 404, 'message' => "No result!"]);
        } catch (NonUniqueResultException $e) {
            return $this->json(['code' => 400, 'message' => "Not unique!"]);
        }
    }
}