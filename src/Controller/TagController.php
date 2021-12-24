<?php

namespace App\Controller;

use App\{Entity\Tag, Repository\ModelRepository};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{HttpFoundation\JsonResponse, HttpFoundation\Request, HttpFoundation\Response};
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
class TagController extends AbstractController implements HasAdminRole
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
        return new JsonResponse($serializer->normalize($tags, 'json'));
    }

    /**
     * @Route("/", name="tag_new", methods={"POST"})
     */
    public function new(Request $request): Response
    {
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
