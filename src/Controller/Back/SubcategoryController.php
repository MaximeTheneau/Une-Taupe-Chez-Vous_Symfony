<?php

namespace App\Controller\Back;

use App\Entity\Subcategory;
use App\Form\SubcategoryType;
use App\Repository\SubcategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/subcategory')]
class SubcategoryController extends AbstractController
{
    private $slugger;

    public function __construct(
        SluggerInterface $slugger,
    )
    {
        $this->slugger = $slugger;
    }

    #[Route('/', name: 'app_back_subcategory_index', methods: ['GET'])]
    public function index(SubcategoryRepository $subcategoryRepository): Response
    {
        return $this->render('back/subcategory/index.html.twig', [
            'subcategories' => $subcategoryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_back_subcategory_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SubcategoryRepository $subcategoryRepository): Response
    {
        $subcategory = new Subcategory();
        $form = $this->createForm(SubcategoryType::class, $subcategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Create slug
            if(empty($subcategory->getSlug())) {
                $slug = $this->slugger->slug($subcategory->getName());
                $subcategory->setSlug($slug);
            }

            $subcategoryRepository->save($subcategory, true);

            return $this->redirectToRoute('app_back_subcategory_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/subcategory/new.html.twig', [
            'subcategory' => $subcategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_subcategory_show', methods: ['GET'])]
    public function show(Subcategory $subcategory): Response
    {
        return $this->render('back/subcategory/show.html.twig', [
            'subcategory' => $subcategory,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_subcategory_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Subcategory $subcategory, SubcategoryRepository $subcategoryRepository): Response
    {
        $form = $this->createForm(SubcategoryType::class, $subcategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Create slug
            if( $subcategory->getName() !== $form->get('name')->getData() ) {
                $slug = $this->slugger->slug($subcategory->getName());
                $subcategory->setSlug($slug);
            }
            // Empty slug
            if(empty($subcategory->getSlug())) {
                $slug = $this->slugger->slug($subcategory->getName());
                $subcategory->setSlug($slug);
            }


            $subcategoryRepository->save($subcategory, true);

            return $this->redirectToRoute('app_back_subcategory_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/subcategory/edit.html.twig', [
            'subcategory' => $subcategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_subcategory_delete', methods: ['POST'])]
    public function delete(Request $request, Subcategory $subcategory, SubcategoryRepository $subcategoryRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subcategory->getId(), $request->request->get('_token'))) {
            $subcategoryRepository->remove($subcategory, true);
        }

        return $this->redirectToRoute('app_back_subcategory_index', [], Response::HTTP_SEE_OTHER);
    }
}
