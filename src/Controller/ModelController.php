<?php

namespace App\Controller;

use App\{Entity\Model, Entity\User, Repository\ModelRepository};
use Doctrine\ORM\EntityManagerInterface;
use Google\Cloud\Storage\{StorageClient, StorageObject};
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\{Normalizer\AbstractNormalizer, SerializerInterface};

/**
 * @Route("/api/model")
 */
class ModelController extends AbstractController
{
    private $tokenManager;
    private $serializer;
    private $entityManager;

    public function __construct(
        JWTTokenManagerInterface $tokenManager,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer
    ) {
        $this->tokenManager = $tokenManager;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/", name="model_index", methods={"GET"})
     */
    public function index(Request $request, ModelRepository $modelRepository): JsonResponse
    {
        $permittedSizes = [10, 15, 20, 30];
        $queryParams = $request->query->all();
        $page = $queryParams['page'];
        $size = $queryParams['size'];
        if (!is_numeric($page) || (int)$page < 1 || !is_numeric($size) || (int)$size < 1) {
            return $this->json(["code" => 400, "message" => "Invalid query parameters provided!"], 400);
        }
        $itemsPerPage = (int)$size;
        if (!in_array($itemsPerPage, $permittedSizes)) {
            $sizes = implode(", ", $permittedSizes);
            return $this->json(
                ["code" => 400, "message" => "The size must be one of the following values: $sizes!"],
                400
            );
        }
        $index = ((int)$page - 1) * $itemsPerPage;

        return $this->json($modelRepository->findAllAndPaginate($index, $itemsPerPage));
    }

    /**
     * @Route("/", name="model_new", methods={"POST"})
     */
    public function new(
        Request $request,
        JWTTokenManagerInterface $jwtManager
    ): Response {
        $file = $request->files->get("file");
        $extension = pathinfo($file->getClientOriginalName())["extension"];
        $model = new Model();
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $jwtManager->parse($token);

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $decodedToken["username"]]);
        $requestBody = $request->request->all();
        $model->setName($requestBody["name"])->setExtension($extension)->setPrice($requestBody["price"]);
        $model->setOwner($user);
        $this->entityManager->persist($model);
        $this->entityManager->flush();
        $decodedJson = json_decode(
            file_get_contents(realpath("../config/json_credentials/savvy-octagon-334317-81205c560b3e.json")),
            true
        );
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket('polybase-files');
        $modelName = $model->getId() . "." . $extension;
        $bucket->upload(
            file_get_contents($file), ["name" => $modelName]
        );
        return $this->json("asd");
    }

    /**
     * @Route("/{id}", name="model_show", methods={"GET"})
     */
    public function show(Model $model): Response
    {
        $fileName = $model->getId() . "." . $model->getExtension();
        $object = $this->getObjectFromBucket($model);
        $object->downloadToFile($fileName);
        $filePath = getcwd() . "\\" . $fileName;
        return $this->file(
            $filePath
        );
    }

    /**
     * @Route("/{id}", name="model_edit", methods={"PATCH"})
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        $model = $this->entityManager->getRepository(Model::class)->find($id);
        if (!$model) {
            return $this->json(["code" => 404, "message" => "Model not found!"], 404);
        }
        $this->serializer->deserialize(
            $request->getContent(),
            Model::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $model,
                AbstractNormalizer::IGNORED_ATTRIBUTES => [
                    'id',
                    'rating',
                    'owner',
                    'extension',
                    'approved',
                    'createdOn',
                    'updatedOn',
                    'users'
                ]
            ]
        );
        $this->entityManager->flush();
        return $this->json(['code' => 200, 'message' => "Successfully updated the model!"]);
    }

    /**
     * @Route("/{id}", name="model_delete", methods={"DELETE"})
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        $model = $this->entityManager->getRepository(Model::class)->find($id);
        if (!$model) {
            return $this->json(["code" => 404, "message" => "Model not found!"], 404);
        }
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $this->tokenManager->parse($token);
        if (!in_array("ROLE_ADMIN", $decodedToken["roles"])) {
            return $this->json(["code" => 403, "message" => "Not allowed!"], 403);
        }
        $object = $this->getObjectFromBucket($model);
        $object->delete();
        $this->entityManager->remove($model);
        $this->entityManager->flush();
        return $this->json(['code' => 200, 'message' => "Successfully deleted the model"]);
    }

    private function getObjectFromBucket($model): StorageObject
    {
        $decodedJson = json_decode(
            file_get_contents(realpath("../config/json_credentials/savvy-octagon-334317-81205c560b3e.json")),
            true
        );
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket('polybase-files');
        $fileName = $model->getId() . "." . $model->getExtension();
        return $bucket->object($fileName);
    }
}
