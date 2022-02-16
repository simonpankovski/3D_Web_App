<?php

namespace App\Controller;

use App\DTO\CartDTO;
use App\Entity\Cart;
use App\Entity\Model;
use App\Entity\Texture;
use App\Entity\User;
use App\Repository\CartRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/cart")
 */
class CartController extends AbstractController
{
    public function getUserFromToken(Request $request, JWTTokenManagerInterface $tokenManager): User
    {
        $em = $this->getDoctrine();
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $tokenManager->parse($token);
        $ownerEmail = $decodedToken["username"];
        return $em->getRepository(User::class)->findOneBy(['email' => $ownerEmail]);
    }

    /**
     * @Route("/", methods={"GET"})
     */
    public function index(
        Request $request,
        JWTTokenManagerInterface $tokenManager,
        CartRepository $cartRepository
    ): Response {
        $user = $this->getUserFromToken($request, $tokenManager);
        $cartItems = $cartRepository->findBy(['user' => $user->getId()]);
        $DTOItems = [];
        foreach ($cartItems as $item) {
            $DTOItems[] = new CartDTO(
                $user->getEmail(),
                $item->getName(),
                $item->getType(),
                $item->getPrice(),
                $item->getObjectId(),
                $item->getId()
            );
        }
        return $this->json(['code' => 200, 'message' => $DTOItems]);
    }

    /**
     * @Route("/{id}", methods={"POST"})
     */
    public function addToCart(
        int $id,
        Request $request,
        JWTTokenManagerInterface $tokenManager,
        CartRepository $cartRepository
    ): JsonResponse {
        $em = $this->getDoctrine();
        $user = $this->getUserFromToken($request, $tokenManager);
        $type = $request->query->get("type");

        if ($cartRepository->findBy(['user' => $user->getId(), 'type' => $type, 'objectId' => $id]) != null) {
            return $this->json(['code' => 400, 'message' => 'Item is already in your cart!']);
        }

        $cart = new Cart();

        if ($type == "texture") {
            $texture = $em->getRepository(Texture::class)->find($id);
            $cart->setName($texture->getName())->setObjectId($texture->getId())->setPrice($texture->getPrice())->setUser(
                $user
            );
            $cart->setType("texture");
        } else {
            $model = $em->getRepository(Model::class)->find($id);
            $cart->setName($model->getName())->setObjectId($model->getId())->setPrice($model->getPrice())->setUser(
                $user
            );
            $cart->setType("model");
        }

        $manager = $em->getManager();
        $manager->persist($cart);
        $manager->flush();
        return $this->json(['code' => 200, 'message' => "Added to cart!"]);
    }

    /**
     * @Route("/{id}", methods={"DELETE"})
     */
    public function removeFromCart(
        int $id,
        Request $request,
        JWTTokenManagerInterface $tokenManager,
        CartRepository $cartRepository
    ): JsonResponse {
        $em = $this->getDoctrine();
        $user = $this->getUserFromToken($request, $tokenManager);
        $type = $request->query->get("type");

        $cartItem = $cartRepository->findOneBy(['objectId' => $id, 'type' => $type]);
        if ($cartItem == null) {
            return $this->json(['code' => '404', 'message' => 'Object not found!']);
        }
        $manager = $em->getManager();
        $manager->remove($cartItem);
        $manager->flush();
        return $this->json(['code' => 200, 'message' => 'Success!']);
    }

    /**
     * @Route ("/count", methods={"GET"})
     */
    public function countCartItems(Request $request, JWTTokenManagerInterface $tokenManager, CartRepository $cartRepository)
    {
        $user = $this->getUserFromToken($request, $tokenManager);
        $count = $cartRepository->createQueryBuilder("cart")->select("count(cart.id)")->where("cart.user = :user")->setParameter('user', $user->getId())->getQuery()->getSingleScalarResult();
        return $this->json(['code' => 200, 'message' => $count]);
    }
}