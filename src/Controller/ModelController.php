<?php

namespace App\Controller;

use App\{Entity\Model, Entity\Tag, Entity\User, Repository\ModelRepository, Service\ModelDTOService};
use Doctrine\ORM\EntityManagerInterface;
use Google\Cloud\Storage\{StorageClient, StorageObject};
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{File\File, JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\{Normalizer\AbstractNormalizer, SerializerInterface};
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

    /**
     * @Route("/", name="model_index", methods={"GET"})
     */
    public function index(
        Request $request,
        ModelRepository $modelRepository,
        ModelDTOService $modelDTOService
    ): JsonResponse {
        $permittedSizes = [10, 15, 20, 30];
        $queryParams = $request->query->all();

        $page = array_key_exists('page', $queryParams) ? $queryParams['page'] : 1;
        $size = array_key_exists('size', $queryParams) ? $queryParams['size'] : 10;
        if (!is_numeric($page) || (int)$page < 1 || !is_numeric($size) || (int)$size < 1) {
            return $this->json(["code" => 400, "message" => "Invalid query parameters provided!"], 400);
        }
        $itemsPerPage = in_array((int)$size, $permittedSizes) ? (int)$size : 10;

        $index = ((int)$page - 1) * $itemsPerPage;
        $results = $modelRepository->findAllAndPaginate($index, $itemsPerPage);
        $modelDTOArray = [];
        $decodedJson = json_decode(
            file_get_contents(realpath("../config/json_credentials/savvy-octagon-334317-81205c560b3e.json")),
            true
        );
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket('polybase-files');
        foreach ($results as $result) {
            $options = ['prefix' => "thumbnails/" . $result->getId()];
            $thumbnailLinks = [];
            foreach ($bucket->objects($options) as $object) {
                $thumbnailLinks[] = $bucket->object($object->name())->signedUrl(new \DateTime('1 hour'));
            }
            $modelDTOArray[] = $modelDTOService->convertModelEntityToDTO($result, $thumbnailLinks);
        }
        return $this->json($modelDTOArray);
    }

    /**
     * @Route("/", name="model_new", methods={"POST"})
     */
    public function new(
        Request $request,
        JWTTokenManagerInterface $jwtManager
    ): Response {
        $files = $request->files->get("format");
        $zip = new ZipArchive();
        $file = "";
        if (sizeof($files) === 1) {
            if (pathinfo($files[0]->getClientOriginalName())["extension"] != "zip") {
                return $this->json(['code' => 400, 'message' => 'Invalid file format, zip required!']);
            } else {
                $file = $files[0];
            }
        } else {
            if ($zip->open('test_new.zip', ZipArchive::CREATE) === true) {
                foreach ($files as $file) {
                    $zip->addFile($file->getPathName(), $file->getClientOriginalName());
                }
                $file = $zip->filename;
                $zip->close();
                $file = new File($file);
            }
        }
        $extension = "zip"/* pathinfo($file->getClientOriginalName())["extension"]*/;
        $model = new Model();
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $jwtManager->parse($token);
        $decodedJson = json_decode(
            file_get_contents(realpath("../config/json_credentials/savvy-octagon-334317-81205c560b3e.json")),
            true
        );

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $decodedToken["username"]]);
        $requestBody = $request->request->all();
        $tags = array_key_exists("tags", $requestBody) ? $requestBody["tags"] : null;
        $tags = $this->entityManager->getRepository(Tag::class)->findBy(['name' => json_decode($tags, true)]);

        if (sizeof($tags) > 0) {
            foreach ($tags as $tag) {
                $model->addTag($tag);
            }
        }
        $model->setName($requestBody["name"])->setExtension($extension)->setPrice($requestBody["price"]);
        $model->setOwner($user);
        $this->entityManager->persist($model);
        $this->entityManager->flush();


        $modelName = $model->getId() . "." . $extension;
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket('polybase-files');
        foreach ($request->files->all() as $key => $value) {
            if(is_array($value)) {
                continue;
            }
            $extension = pathinfo($value->getClientOriginalName())["extension"];
            if ($extension == "jpg" || $extension == "png") {
                $bucket->upload(
                    file_get_contents($value),
                    ["name" => "thumbnails/" . $model->getId() . "_" . $key . "." . $extension]
                );
            }
        }
        $bucket->upload(
            file_get_contents($file),
            ["name" => $modelName]);

        return $this->json("asd");
    }

    /**
     * @Route("/{id}", name="model_show", methods={"GET"})
     */
    public function show(?Model $model = null): Response
    {
        if (!$model) {
            return $this->json(['code' => 404, 'message' => 'Model not found!'], 404);
        }
        $decodedJson = json_decode(
            file_get_contents(realpath("../config/json_credentials/savvy-octagon-334317-81205c560b3e.json")),
            true
        );
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $storage->registerStreamWrapper();
        $bucket = $storage->bucket('polybase-files');
        $bucket->object($model->getId() . "." . $model->getExtension())->downloadToFile("public.zip");

        return $this->file(getcwd() . "\public.zip");
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
                    'extension',
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
        if (!$model) {
            return $this->json(["code" => 404, "message" => "Model not found!"], 404);
        }
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $this->tokenManager->parse($token);
        $ownerEmail = $decodedToken["username"];
        if (!($model->getOwner()->getEmail() === $ownerEmail)) {
            return $this->json(["code" => 403, "message" => "Not allowed!"], 403);
        }
        $object = $this->getObjectFromBucket($model);
        $object->delete();
        $this->entityManager->remove($model);
        $this->entityManager->flush();
        return $this->json(['code' => 200, 'message' => "Successfully deleted the model"]);
    }
}
