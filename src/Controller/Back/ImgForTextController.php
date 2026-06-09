<?php

namespace App\Controller\Back;

use App\Entity\Posts;
use App\Service\ImageOptimizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImgForTextController extends AbstractController
{
    private $imageOptimizer;

    public function __construct(ImageOptimizer $imageOptimizer)
    {
        $this->imageOptimizer = $imageOptimizer;
    }

    #[Route('/uploadImg', name: 'image_upload', methods: [ 'POST'])]
    public function uploadImg(Request $request, EntityManagerInterface $entityManager ): JsonResponse
    {
        $file = $request->files->get('upload');
        $id = $request->query->get('id');
        $paragraph = $request->query->get('paragraph');
        // dd($paragraph);
        $post = $entityManager->getRepository(Posts::class)->find($id);

        if ($file) {
            $slug = $post->getSlug() . '-' . $post->getId() + 1;


            $this->imageOptimizer->setPicture($file, $post , $slug);

            // Retourner l'URL de l'image téléchargée
            $url =  $_ENV['DOMAIN_IMG'] . $slug . '.webp';

            return new JsonResponse(['url' => $post->getImgPost()]);
        }

        return new JsonResponse(['error' => 'Aucun fichier téléchargé'], 400);
    }
    #[Route('/admin/upload-image', name: 'admin_upload_image', methods: ['POST'])]
    public function uploadEditorImage(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $file = $request->files->get('upload');

        if (!$file) {
            return new JsonResponse([
                'uploaded' => 0,
                'error' => ['message' => 'Aucun fichier téléchargé'],
            ], 400);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext  = $file->guessExtension() ?? 'webp';
        $slug = $slugger->slug($originalFilename)->lower() . '-' . uniqid();

        $url = $this->imageOptimizer->uploadRawToS3($file, $slug);

        return new JsonResponse([
            'uploaded' => 1,
            'fileName' => $slug . '.' . $ext,
            'url'      => $url,
        ]);
    }
}
