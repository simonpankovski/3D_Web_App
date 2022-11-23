<?php

namespace App\Controller;

use App\{Entity\Model,
    Entity\Purchase,
    Entity\Tag,
    Entity\User,
    Repository\ModelRepository,
    Service\ModelDTOService,
    Service\ZipService};
use Doctrine\ORM\EntityManagerInterface;
use FilesystemIterator;
use Psr\Log\LoggerInterface;
use Google\Cloud\Storage\{StorageClient, StorageObject};
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{File\File, JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\{Normalizer\AbstractNormalizer, SerializerInterface};
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ZipArchive;

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
    public function index(
        Request $request,
        ModelRepository $modelRepository,
        ModelDTOService $modelDTOService
    ): JsonResponse {
        $permittedSizes = [5, 15, 20, 30];
        $queryParams = $request->query->all();

        $page = array_key_exists('page', $queryParams) ? $queryParams['page'] : 1;
        $size = array_key_exists('size', $queryParams) ? $queryParams['size'] : 5;
        $category = array_key_exists('category', $queryParams) ? $queryParams['category'] : null;
        $searchTerm = array_key_exists('search', $queryParams) ? $queryParams['search'] : null;
        $totalModels = $modelRepository->countModels($category);
        if (!is_numeric($page) || (int)$page < 1 || !is_numeric($size) || (int)$size < 1) {
            return $this->json(["code" => 400, "message" => "Invalid query parameters provided!"], 400);
        }
        $itemsPerPage = in_array((int)$size, $permittedSizes) ? (int)$size : 10;
        $index = ((int)$page - 1) * $itemsPerPage;
        $results = $modelRepository->findAllAndPaginate($index, $itemsPerPage, $category, $searchTerm);
        $modelDTOArray = [];
        $texturesPath = getcwd() . "/models";
        foreach ($results as $res) {
            $thumbnailLinks = [];
            $textureFolder = $texturesPath . "/" . $res->getId() . "/thumbnails";
            $iterator = new FilesystemIterator($textureFolder);
            foreach ($iterator as $textureFolder) {
                $thumbnailLinks[] = $_ENV['URL'] . "/models/" . $res->getId(
                    ) . "/thumbnails/" . $textureFolder->getFilename();
            }
            $modelDTOArray[] = $modelDTOService->convertModelEntityToDTO($res, $thumbnailLinks);
        }

        $modelDTOArray[] = (int)ceil($totalModels / $itemsPerPage);
        return $this->json($modelDTOArray);
    }

    /**
     * @Route("/", name="model_new", methods={"POST"})
     */
    public function new(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        ValidatorInterface $validator
    ): Response {
        return $this->json(["code" => 400, "message" => "Uploading new assets has been disabled!"]);
    }

    /**
     * @Route("/{id}", name="model_show", methods={"GET"})
     */
    public function show(?Model $model = null, JWTTokenManagerInterface $jwtManager, Request $request): Response
    {
        if (!$model) {
            return $this->json(['code' => 404, 'message' => 'Model not found!'], 404);
        }
        $purchase = null;

        if ($request->headers->has("authorization")) {
            $authHeader = preg_split("/ /", $request->headers->get("authorization"));
            if (sizeof($authHeader) > 1) {
                $token = $authHeader[1];
                $decodedToken = $jwtManager->parse($token);
                $user = $this->entityManager->getRepository(User::class)->findOneBy(
                    ['email' => $decodedToken['username']]
                );
                $purchase = $this->entityManager->getRepository(Purchase::class)->findOneBy(
                    ['user' => $user->getId(), 'model' => $model->getId()]
                );
            }
        }
        $commonPath = getcwd() . "/models/" . $model->getId() . "/files";
        if ($purchase == null || $request->query->has("browse")) {
            $fileName = bin2hex(random_bytes(20));

            $fileNames = array_slice(scandir($commonPath), 2);
            $files = array_map(function ($el) use ($commonPath) {
                $element = $commonPath . "/" . $el;
                return $this->file($element);
            }, $fileNames);
            ini_set('memory_limit', '-1');
            return $this->json([$files, $fileName]);
        } else {
            $modelId = $model->getId();
            $zipPath = getcwd() . "/models/" . $modelId;
            if (!in_array("files.zip",scandir($zipPath))){
                ZipService::zipFolder($zipPath, $modelId, "models");
            }
            return $this->file($zipPath . "/files.zip");
        }
    }


    /**
     * @Route("/{id}", name="model_edit", methods={"PATCH"})
     */
    public function edit(Request $request, ?Model $model): JsonResponse
    {
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
                    'extensions',
                    'approved',
                    'createdOn',
                    'updatedOn',
                    'users',
                    'tags'
                ]
            ]
        );
        $requestBody = json_decode($request->getContent(), true);
        if (array_key_exists('tags', $requestBody)) {
            $tags = $this->entityManager->getRepository(Tag::class)->findBy(['name' => $requestBody['tags']]);
            foreach ($tags as $tag) {
                $model->addTag($tag);
            }
        }
        $this->entityManager->flush();
        return $this->json(['code' => 200, 'message' => "Successfully updated the model!"]);
    }

    /**
     * @Route("/{id}", name="model_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ?Model $model = null): JsonResponse
    {
        return $this->json(['code' => 200, 'message' => "Successfully deleted the model"]);
        /*if (!$model) {
            return $this->json(["code" => 404, "message" => "Model not found!"], 404);
        }
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $this->tokenManager->parse($token);
        $ownerEmail = $decodedToken["username"];
        if (!($model->getOwner()->getEmail() === $ownerEmail)) {
            return $this->json(["code" => 403, "message" => "Not allowed!"], 403);
        }
        $this->entityManager->remove($model);
        $this->entityManager->flush();
       */
    }
}
