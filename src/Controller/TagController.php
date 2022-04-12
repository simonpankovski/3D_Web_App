<?php

namespace App\Controller;

use App\{Entity\Tag, Entity\User, Repository\ModelRepository};
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{HttpFoundation\JsonResponse,
    HttpFoundation\Request,
    HttpFoundation\Response,
    Validator\Validator\ValidatorInterface};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\{Encoder\JsonEncoder,
    Normalizer\AbstractNormalizer,
    Normalizer\ObjectNormalizer,
    Serializer,
    SerializerInterface
};

/**
 * @Route("/api/tag")
 */
class TagController extends AbstractController //implements HasAdminRole
{
    private $serializer;
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function checkIsAdmin(Request $request, JWTTokenManagerInterface $tokenManager): bool
    {
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $tokenManager->parse($token);

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $decodedToken["username"]]);
        if ($user == null || !in_array('ADMIN_ROLE', $user->getRoles())) {
            return false;
        }
        return true;
    }

    /**
     * @Route("/", name="tag_index", methods={"GET"})
     */
    public function index(): Response
    {
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getName();
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);

        $serializer = new Serializer([$normalizer], [$encoder]);
        $tags = $this->entityManager->getRepository(Tag::class)->findAll();
        $tagNames = [];
        foreach ($tags as $tag) {
            $tagNames[] = $tag->getName();
        }
        return new JsonResponse($serializer->normalize($tagNames, 'json'));
    }

    /**
     * @Route("/", name="tag_new", methods={"POST"})
     */
    public function new(Request $request, JWTTokenManagerInterface $tokenManager, ValidatorInterface $validator): Response
    {
        if (!$this->checkIsAdmin($request, $tokenManager)) {
            return $this->json(['code' => 401, 'message' => 'Unauthorized!'], 401);
        }
        $tag = new Tag();
        $this->serializer->deserialize(
            $request->getContent(),
            Tag::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $tag,
                AbstractNormalizer::IGNORED_ATTRIBUTES => ['models']
            ]
        );
        if($this->entityManager->getRepository(Tag::class)->findOneBy(['name' => $tag->getName()]) != null ) {
            return $this->json(['code' => 400, 'message' => 'A tag already exists with that name!'], 400);
        }
        $errors = $validator->validate($tag);
        if (count($errors) > 0){
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['code' => 400, 'message' => $errorMessages], 400);
        }
        $this->entityManager->persist($tag);
        $this->entityManager->flush();
        return $this->json(['code' => 200, 'message' => 'Created!']);
    }

    /**
     * @Route("/{id}", name="tag_show", methods={"GET"})
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function show(?Tag $tag = null): Response
    {
        if (!$tag) {
            return $this->json(['code' => 404, 'message' => 'Tag not found!']);
        }
        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getName();
            },
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);

        $serializer = new Serializer([$normalizer], [$encoder]);
        return new JsonResponse($serializer->normalize($tag, 'json'));
    }

    /**
     * @Route("/{id}", name="tag_edit", methods={"PATCH"})
     */
    public function edit(Request $request, ?Tag $tag = null, ModelRepository $modelRepository): Response
    {
        if (!$tag) {
            return $this->json(['code' => 404, 'message' => 'Tag was not found!']);
        }
        $requestBody = json_decode($request->getContent(), true);
        $name = array_key_exists('name', $requestBody) ? $requestBody['name'] : $tag->getName();
        $models = [];
        if (array_key_exists('models', $requestBody)) {
            $models = $modelRepository->findBy(['id' => $requestBody['models']]);
        }
        $tag->setName($name)->addModel($models[0]);
        $this->entityManager->flush();
        return $this->json(['code' => 200, 'message' => 'Updated!']);
    }

    /**
     * @Route("/{id}", name="tag_delete", methods={"DELETE"})
     */
    public function delete(?Tag $tag = null): JsonResponse
    {
        if (!$tag) {
            return $this->json(['code' => 404, 'message' => 'Tag was not found!']);
        }
        $models = $tag->getModels();
        foreach ($models as $model) {
            $model->removeTag($tag);
        }
        $this->entityManager->remove($tag);
        $this->entityManager->flush();
        return $this->json(['code' => 200, 'message' => 'Deleted successfully']);
    }
}
