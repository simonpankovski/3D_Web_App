<?php

namespace App\Controller;

use App\{Entity\Model, Entity\Purchase, Entity\Tag, Entity\User, Repository\ModelRepository, Service\ModelDTOService};
use Doctrine\ORM\EntityManagerInterface;
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

    private function getObjectFromBucket($model): StorageObject
    {
        $decodedJson = json_decode(
            file_get_contents(realpath($_ENV['GOOGLE_APPLICATION_CREDENTIALS'])),
            true
        );
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket($_ENV['BUCKET_NAME']);
        $fileName = $model->getId() . "." . $model->getExtensions();
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
        $decodedJson = json_decode(
            file_get_contents(realpath($_ENV['GOOGLE_APPLICATION_CREDENTIALS'])),
            true
        );
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket($_ENV['BUCKET_NAME']);
        foreach ($results as $result) {
            $options = ['prefix' => "thumbnails/" . $result->getId()];
            $thumbnailLinks = [];
            foreach ($bucket->objects($options) as $object) {
                $thumbnailLinks[] = $bucket->object($object->name())->signedUrl(new \DateTime('1 hour'));
            }
            $modelDTOArray[] = $modelDTOService->convertModelEntityToDTO($result, $thumbnailLinks);
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
        $modelRepo = $this->entityManager->getRepository(Model::class);
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $jwtManager->parse($token);
        $decodedJson = json_decode(
            file_get_contents(realpath($_ENV['GOOGLE_APPLICATION_CREDENTIALS'])),
            true
        );

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $decodedToken["username"]]);
        $query = $modelRepo->createQueryBuilder('m')->select('count(m.id)')->where('m.owner = :owner')->andWhere(
            'm.approved = false'
        )->setParameter('owner', $user->getId())->getQuery()->getSingleScalarResult();
        if ($query > 0) {
            return $this->json(
                ['code' => 400, 'message' => 'Maximum number of unapproved uploads reached, please try again later!'],
                400
            );
        }
        $files = $request->files->get("format");
        dd($files);
        if ($files == null) {
            return $this->json(['code' => 400, 'message' => 'No files were attached'], 400);
        }
        $zip = new ZipArchive();
        $requestBody = $request->request->all();
        $file = "";
        $extensionsArray = [];
        $zipName = bin2hex(random_bytes(20)) . ".zip";
        if ($zip->open($zipName, ZipArchive::CREATE) === true) {
            foreach ($files as $file) {
                $zip->addFile($file->getPathName(), $file->getClientOriginalName());
                $extensionsArray[] = pathinfo($file->getClientOriginalName())["extension"];
            }
            $file = $zip->filename;
            $zip->close();
            $file = new File($file);
        }
        array_unshift($extensionsArray, "zip");
        $model = new Model();

        $tags = array_key_exists("tags", $requestBody) ? $requestBody["tags"] : null;
        $tags = $this->entityManager->getRepository(Tag::class)->findBy(['name' => json_decode($tags, true)]);

        if (sizeof($tags) > 0) {
            foreach ($tags as $tag) {
                $model->addTag($tag);
            }
        }
        $model->setName($requestBody["name"])->setExtensions($extensionsArray)->setPrice(
            $requestBody["price"]
        )->setCategory($requestBody['category']);
        $model->setOwner($user);
        $errors = $validator->validate($model);
        if (count($errors) > 0) {
            unlink($file);
            return $this->json(['code' => 400, 'message' => "Model is not valid!"], 400);
        }
        $this->entityManager->persist($model);
        $this->entityManager->flush();


        $modelName = $model->getId() . "." . $extensionsArray[0];
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket($_ENV['BUCKET_NAME']);
        foreach ($request->files->get("thumbnails") as $key => $value) {
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
            ["name" => $modelName]
        );
        unlink($file);
        return $this->json(["code" => 200, "message" => "Success!"]);
    }

    /**
     * @Route("/{id}", name="model_show", methods={"GET"})
     */
    public function show(?Model $model = null, JWTTokenManagerInterface $jwtManager, Request $request): Response
    {
        if (!$model) {
            return $this->json(['code' => 404, 'message' => 'Model not found!'], 404);
        }
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $jwtManager->parse($token);
        $decodedJson = json_decode(
            file_get_contents(realpath($_ENV['GOOGLE_APPLICATION_CREDENTIALS'])),
            true
        );
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $decodedToken['username']]);
        $purchase = $this->entityManager->getRepository(Purchase::class)->findOneBy(
            ['user' => $user->getId(), 'model' => $model->getId()]
        );
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $storage->registerStreamWrapper();
        $bucket = $storage->bucket($_ENV['BUCKET_NAME']);
        $fileName = bin2hex(random_bytes(20));
        mkdir(getcwd() . "\\models\\" . $fileName);
        $bucket->object($model->getId() . "." . $model->getExtensions()[0])->downloadToFile(
            getcwd() . "\\models\\" . $fileName . "\\public.zip"
        );
        $commonPath = getcwd() . "\\models\\" . $fileName;
        if ($purchase == null || $request->query->has("browse")) {
            $zip = new ZipArchive;
            $pathToZip = $commonPath . "\\public.zip";
            if ($zip->open($pathToZip) === true) {
                $zip->extractTo($commonPath);
                $zip->close();
                unlink($commonPath . "\\public.zip");
            } else {
                return $this->json("not");
            }
            $fileNames = array_slice(scandir($commonPath), 2);
            $files = array_map(function ($el) use ($commonPath) {
                $element = $commonPath . "\\" . $el;
                return $this->file($element);
            }, $fileNames);
            /*$modelName = preg_grep ('/\.fbx/i', $fileNames);
            $name = reset($modelName);
            $file = $this->file($commonPath . "\\" . $name);*/
            ini_set('memory_limit', '-1');
            return $this->json([$files, $fileName]);
        } else {
            return $this->file($commonPath . "\\public.zip");
        }
    }

    /**
     * @Route("/textures/{id}", methods={"GET"})
     */
    public function getTextures(Request $request): JsonResponse
    {
        $targetDir = getcwd() . "\\models\\" . $request->query->get("key");
        if (!is_dir($targetDir)) {
            return $this->json(['code' => 404, 'message' => 'Not Found!']);
        }
        $filesInModelsDir = scandir(getcwd() . "\\models\\" . $request->query->get("key"));
        $imageNames = array_filter($filesInModelsDir, function ($el) {
            return str_contains($el, 'png') || str_contains($el, 'jpg');
        });
        //$fileNames = scandir();
        $files = array_map(function ($el) use ($targetDir) {
            $element = $targetDir . "\\" . $el;
            return $this->file($element);
        }, $imageNames);
        return $this->json($files);
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
