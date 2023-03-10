<?php

namespace App\Controller\Back;

use App\Entity\Pages;
use App\Form\PagesType;
use App\Repository\PagesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use App\Service\ImageOptimizer;

#[Route('/pages')]
class PagesController extends AbstractController
{
    private $imageOptimizer;
    private $slugger;
    private $photoDir;
    private $params;
    private $projectDir;

    public function __construct(
        ContainerBagInterface $params,
        ImageOptimizer $imageOptimizer,
        SluggerInterface $slugger,
    )
    {
        $this->params = $params;
        $this->imageOptimizer = $imageOptimizer;
        $this->slugger = $slugger;
        $this->projectDir =  $this->params->get('app.projectDir');
        $this->photoDir =  $this->params->get('app.imgDir');
    }

    #[Route('/', name: 'app_back_pages_index', methods: ['GET'])]
    public function index(PagesRepository $pagesRepository): Response
    {        
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('back/pages/index.html.twig', [
            'pages' => $pagesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_back_pages_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PagesRepository $pagesRepository): Response
    {   
        $this->denyAccessUnlessGranted('ROLE_USER');
        $page = new Pages();
        $form = $this->createForm(PagesType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $slug = $this->slugger->slug($page->getTitle());
            $page->setSlug($slug);

            // IMAGE 1
            $this->imageOptimizer->setPicture($form->get('imgHeader')->getData(), $page, 'setImgHeader', $slug );

            // IMAGE 2
            if ($form->get('imgHeader2')->getData() != null) {
                $this->imageOptimizer->setPicture($form->get('imgHeader2')->getData(), $page, 'setImgHeader2', $slug );
            }
        
            $pagesRepository->save($page, true);
            
            return $this->redirectToRoute('app_back_pages_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('back/pages/new.html.twig', [
            'page' => $page,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_pages_show', methods: ['GET'])]
    public function show(Pages $page): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('back/pages/show.html.twig', [
            'page' => $page,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_pages_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pages $page, PagesRepository $pagesRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $form = $this->createForm(PagesType::class, $page);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $this->slugger->slug($page->getTitle());
            $page->setSlug($slug);

            // IMAGE 1
            if ($form->get('imgHeader')->getData() != null) {
                $this->imageOptimizer->setPicture($form->get('imgHeader')->getData(), $page, 'setImgHeader', $slug );
                $this->imageOptimizer->setThumbnailJpg($form->get('imgHeader')->getData(), $page, 'setImgHeaderJpg', $slug );
            }

            // IMAGE 2
            if ($form->get('imgHeader2')->getData() != null) {
                $this->imageOptimizer->setPicture($form->get('imgHeader2')->getData(), $page, 'setImgHeader2', $slug.'-2' );
            }


            $pagesRepository->save($page, true);
            return $this->redirectToRoute('app_back_pages_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('back/pages/edit.html.twig', [
            'page' => $page,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_pages_delete', methods: ['POST'])]
    public function delete(Request $request, Pages $page, PagesRepository $pagesRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->isCsrfTokenValid('delete'.$page->getId(), $request->request->get('_token'))) {
            $pagesRepository->remove($page, true);
        }

        return $this->redirectToRoute('app_back_pages_index', [], Response::HTTP_SEE_OTHER);
    }
}
