<?php

namespace App\Controller;

use App\Entity\PostResponse;
use App\Entity\Texture;
use App\Entity\User;
use App\Repository\TextureRepository;
use App\Service\TextureDTOService;
use Doctrine\ORM\EntityManagerInterface;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use ZipArchive;

/**
 * @Route("/api/texture")
 */
class TextureController extends AbstractController implements PostResponse
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

    private function getObjectFromBucket($texture): StorageObject
    {
        $decodedJson = json_decode(
            file_get_contents(realpath("../config/json_credentials/savvy-octagon-334317-81205c560b3e.json")),
            true
        );
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket('polybase-files');
        $fileName = $texture->getId() . "." . "zip";
        return $bucket->object("texture/" . $fileName);
    }

    /**
     * @Route("/", name="texture_index", methods={"GET"})
     */
    public function index(
        Request $request,
        TextureRepository $textureRepository,
        TextureDTOService $textureDTOService
    ): JsonResponse {
        $permittedSizes = [5, 15, 20, 30];
        $queryParams = $request->query->all();
        $page = array_key_exists('page', $queryParams) ? $queryParams['page'] : 1;
        $size = array_key_exists('size', $queryParams) ? $queryParams['size'] : 5;
        $category = array_key_exists('category', $queryParams) ? $queryParams['category'] : null;
        $totalTextures = $textureRepository->countTextures($category);
        if (!is_numeric($page) || (int)$page < 1 || !is_numeric($size) || (int)$size < 1) {
            return $this->json(["code" => 400, "message" => "Invalid query parameters provided!"], 400);
        }
        $itemsPerPage = in_array((int)$size, $permittedSizes) ? (int)$size : 10;
        $index = ((int)$page - 1) * $itemsPerPage;
        $results = $textureRepository->findAllAndPaginate($index, $itemsPerPage, $category);
        $resultDTO = [];
        $decodedJson = json_decode(
            file_get_contents(realpath("../config/json_credentials/savvy-octagon-334317-81205c560b3e.json")),
            true
        );
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket('polybase-files');
        foreach ($results as $res) {
            $options = ['prefix' => "textures/thumbnails/" . $res->getId()];
            $thumbnailLinks = [];
            foreach ($bucket->objects($options) as $object) {
                $thumbnailLinks[] = $bucket->object($object->name())->signedUrl(new \DateTime('1 hour'));
            }
            $resultDTO[] = $textureDTOService->convertModelEntityToDTO($res, $thumbnailLinks);
        }
        $resultDTO[] = (int)ceil($totalTextures / $itemsPerPage);
        return $this->json($resultDTO);
    }

    /**
     * @Route("/", name="texture_new", methods={"POST"})
     */
    public function new(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $files = $request->files->get("format");
        if ($files == null) {
            return $this->json(['code' => 400, 'message' => 'No files were attached']);
        }
        $zip = new ZipArchive();
        $requestBody = $request->request->all();
        $file = "";
        $textureRepo = $this->entityManager->getRepository(Texture::class);
        if (sizeof($files) === 1) {
            if (pathinfo($files[0]->getClientOriginalName())["extension"] != "zip") {
                return $this->json(['code' => 400, 'message' => 'Invalid file format, zip required!']);
            } else {
                $file = $files[0];
            }
        } else {
            $zipName = bin2hex(random_bytes(20));
            if ($zip->open($zipName . '.zip', ZipArchive::CREATE) === true) {
                foreach ($files as $file) {
                    $zip->addFile($file->getPathName(), $file->getClientOriginalName());
                }
                $file = $zip->filename;
                $zip->close();
                $file = new File($file);
            }
        }
        $token = preg_split("/ /", $request->headers->get("authorization"))[1];
        $decodedToken = $jwtManager->parse($token);

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $decodedToken["username"]]);
        $query = $textureRepo->createQueryBuilder('t')->select('count(t.id)')->where('t.owner = :owner')->andWhere(
            't.approved = false'
        )->setParameter('owner', $user->getId())->getQuery()->getSingleScalarResult();
        if ($query > 0) {
            unlink($file);
            return $this->json(
                ['code' => 400, 'message' => 'Maximum number of unapproved uploads reached, please try again later!']
            );
        }
        $texture = new Texture();
        $texture->setName($requestBody["name"])->setPrice(
            $requestBody["price"]
        )->setCategory($requestBody['category']);
        $texture->setOwner($user);
        $errors = $validator->validate($texture);
        if (count($errors) > 0) {
            return $this->json(['code' => 400, 'message' => "Texture is not valid!"], 400);
        }
        $this->entityManager->persist($texture);
        $this->entityManager->flush();
        $decodedJson = json_decode(
            file_get_contents(realpath("../config/json_credentials/savvy-octagon-334317-81205c560b3e.json")),
            true
        );
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $bucket = $storage->bucket('polybase-files');
        foreach ($request->files->get("thumbnails") as $key => $value) {
            $extension = pathinfo($value->getClientOriginalName())["extension"];
            if ($extension == "jpg" || $extension == "png") {
                $bucket->upload(
                    file_get_contents($value),
                    ["name" => "textures/thumbnails/" . $texture->getId() . "_" . $key . "." . $extension]
                );
            }
        }
        $bucket->upload(
            file_get_contents($file),
            ["name" => "textures/" . $texture->getId() . ".zip"]
        );
        unlink($file);
        //$textureDTOService->convertModelEntityToDTO($texture, [])
        return $this->json(["code" => 200, "message" => "Success"]);
    }

    /**
     * @Route("/{id}", name="texture_show", methods={"GET"})
     */
    public function show(?Texture $texture = null, JWTTokenManagerInterface $jwtManager, Request $request): JsonResponse
    {
        if (!$texture) {
             return $this->json(['code' => 404, 'message' => 'Texture not found!'], 404);
         }
         $token = preg_split("/ /", $request->headers->get("authorization"))[1];
         $decodedToken = $jwtManager->parse($token);
         $decodedJson = json_decode(
             file_get_contents(realpath("../config/json_credentials/savvy-octagon-334317-81205c560b3e.json")),
             true
         );
         $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $decodedToken['username']]);
         //$purchase = $this->entityManager->getRepository(Purchase::class)->findOneBy(['user'=> $user->getId(), 'model' => $model->getId()]);
        /*if($purchase == null) {
            return $this->json(["code" => 403, "Forbidden!"], 403);
        }*/
        $storage = new StorageClient([
                                         'keyFile' => $decodedJson
                                     ]);
        $storage->registerStreamWrapper();
        $bucket = $storage->bucket('polybase-files');
        $zipName = bin2hex(random_bytes(20));
        $zipPath = getcwd() . "\\models\\textures\\" . $zipName . ".zip";
        $bucket->object("textures/" . $texture->getId() . ".zip")->downloadToFile($zipPath);
        $zip = new ZipArchive;

        if ($zip->open($zipPath) === TRUE) {
            $extractPath = getcwd() . "\\models\\textures\\" . $zipName;
            mkdir($extractPath);
            $zip->extractTo($extractPath);
            $zip->close();
            unlink($zipPath);

            $fileNames = scandir($extractPath);
            $files = array_map(function ($el) use ($extractPath) {
                $element = $extractPath . "\\" . $el;
                return $this->file($element);
            }, array_slice($fileNames, 2));
            return $this->json(["code" => 200, "message" => $files], 200);
        } else {
            return $this->json(["code" => 400, "message" => "Could not load the texture!"], 400);
        }
    }
}