<?php

namespace App\Controller;

use App\Entity\PostResponse;
use App\Entity\Texture;
use App\Entity\TexturePurchase;
use App\Entity\User;
use App\Repository\TextureRepository;
use App\Service\TextureDTOService;
use App\Service\ZipService;
use Doctrine\ORM\EntityManagerInterface;
use FilesystemIterator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
        $searchTerm = array_key_exists('search', $queryParams) ? $queryParams['search'] : null;

        $totalTextures = $textureRepository->countTextures($category);
        if (!is_numeric($page) || (int)$page < 1 || !is_numeric($size) || (int)$size < 1) {
            return $this->json(["code" => 400, "message" => "Invalid query parameters provided!"], 400);
        }
        $itemsPerPage = in_array((int)$size, $permittedSizes) ? (int)$size : 10;
        $index = ((int)$page - 1) * $itemsPerPage;
        $results = $textureRepository->findAllAndPaginate($index, $itemsPerPage, $category, $searchTerm);
        $resultDTO = [];
        $texturesPath = getcwd() . "/textures";

        foreach ($results as $res) {
            $thumbnailLinks = [];
            $textureFolder = $texturesPath . "/" . $res->getId() . "/thumbnails";
            $iterator = new FilesystemIterator($textureFolder);
            foreach ($iterator as $textureFolder) {
                $thumbnailLinks[] = $_ENV['URL'] . "/textures/" . $res->getId(
                    ) . "/thumbnails/" . $textureFolder->getFilename();
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
        return $this->json(["code" => 400, "message" => "Uploading new assets has been disabled!"]);
    }

    /**
     * @Route("/{id}", name="texture_show", methods={"GET"})
     */
    public function show(?Texture $texture = null, JWTTokenManagerInterface $jwtManager, Request $request): Response
    {
        if (!$texture) {
            return $this->json(['code' => 404, 'message' => 'Texture not found!'], 404);
        }
        $textureId = $texture->getId();
        $purchase = null;
        if ($request->headers->has("authorization")) {
            $authHeader = preg_split("/ /", $request->headers->get("authorization"));
            if (sizeof($authHeader) > 1) {
                $token = $authHeader[1];
                $decodedToken = $jwtManager->parse($token);
                $user = $this->entityManager->getRepository(User::class)->findOneBy(
                    ['email' => $decodedToken['username']]
                );
                $purchase = $this->entityManager->getRepository(TexturePurchase::class)->findOneBy(
                    ['user' => $user->getId(), 'texture' => $textureId]
                );
            }
        }

        if ($purchase != null && !$request->query->has("browse")) {
            $zipPath = getcwd() . "/textures/" . $textureId;
            if (!in_array("files.zip",scandir($zipPath))){
                ZipService::zipFolder($zipPath, $textureId, "textures");
            }
            return $this->file($zipPath . "/files.zip");

        }
        $filesPath = getcwd() . "/textures/" . $textureId . "/";
        try {
            $extractPath = $filesPath . "files";
            $fileNames = scandir($extractPath);
            $files = array_map(function ($el) use ($extractPath){
                $element = $extractPath . "/" . $el;
                return $this->file($element);
            }, array_slice($fileNames, 2));
            ini_set('memory_limit', '-1');
            return $this->json(["code" => 200, "message" => $files]);
        } catch (\ErrorException $error) {
            return $this->json(["code" => 400, "message" => "Could not load the texture!"], 400);
        }
    }

    /**
     * @Route("/{id}", name="model_edit", methods={"PATCH"})
     */
    public function edit(Request $request, ?Texture $texture): JsonResponse
    {
        if (!$texture) {
            return $this->json(["code" => 404, "message" => "Texture not found!"], 404);
        }
        $this->serializer->deserialize(
            $request->getContent(),
            Texture::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $texture,
                AbstractNormalizer::IGNORED_ATTRIBUTES => [
                    'id',
                    'rating',
                    'owner',
                    'approved',
                    'createdOn',
                    'updatedOn'
                ]
            ]
        );
        $this->entityManager->flush();
        return $this->json(['code' => 200, 'message' => "Successfully updated the texture!"]);
    }

    /**
     * @Route("/{id}", name="texture_delete", methods={"DELETE"})
     */
    public function delete(Request $request, ?Texture $texture = null): JsonResponse
    {
        if (!$texture) {
            return $this->json(["code" => 404, "message" => "Texture not found!"], 404);
        }
        return $this->json(['code' => 200, 'message' => "Successfully deleted the texture"]);
    }
}