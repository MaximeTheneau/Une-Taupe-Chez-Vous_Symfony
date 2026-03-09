<?php

namespace App\Controller\Back;

use App\Entity\Keyword;
use App\Form\KeywordType;
use App\Repository\KeywordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/keyword')]
class KeywordController extends AbstractController
{
    #[Route('/', name: 'app_back_keyword_index', methods: ['GET'])]
    public function index(KeywordRepository $keywordRepository): Response
    {
        return $this->render('back/keyword/index.html.twig', [
            'keywords' => $keywordRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_back_keyword_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $keyword = new Keyword();
        $form = $this->createForm(KeywordType::class, $keyword);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($keyword);
            $entityManager->flush();

            return $this->redirectToRoute('app_back_keyword_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/keyword/new.html.twig', [
            'keyword' => $keyword,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_keyword_show', methods: ['GET'])]
    public function show(Keyword $keyword): Response
    {
        return $this->render('back/keyword/show.html.twig', [
            'keyword' => $keyword,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_keyword_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Keyword $keyword, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(KeywordType::class, $keyword);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_back_keyword_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/keyword/edit.html.twig', [
            'keyword' => $keyword,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_keyword_delete', methods: ['POST'])]
    public function delete(Request $request, Keyword $keyword, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$keyword->getId(), $request->request->get('_token'))) {
            $entityManager->remove($keyword);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_back_keyword_index', [], Response::HTTP_SEE_OTHER);
    }
}
