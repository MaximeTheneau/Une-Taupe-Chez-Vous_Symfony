<?php

namespace App\Controller\Back;

use App\Entity\Posts;
use App\Entity\Category;
use App\Entity\ParagraphPosts;
use App\Form\PostsType;
use App\Form\ParagraphPostsType;
use App\Message\TriggerNextJsBuild;
use App\Repository\PostsRepository;
use App\Repository\ParagraphPostsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Messenger\MessageBusInterface;
use DateTime;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use App\Service\ImageOptimizer;
use Doctrine\ORM\EntityManagerInterface;
use Michelf\MarkdownExtra;
use \IntlDateFormatter;
use App\Service\MarkdownProcessor;
use App\Service\UrlGeneratorService;
use Symfony\Component\String\UnicodeString;
use DOMDocument;


#[Route('/posts')]
class PostsController extends AbstractController
{
    private $params;
    private $imageOptimizer;
    private $slugger;
    private $photoDir;
    private $projectDir;
    private $entityManager;
    private $markdown;
    private $markdownProcessor;
    private $urlGeneratorService;

    public function __construct(
        ContainerBagInterface $params,
        ImageOptimizer $imageOptimizer,
        SluggerInterface $slugger,
        EntityManagerInterface $entityManager,
        MarkdownProcessor $markdownProcessor,
        UrlGeneratorService $urlGeneratorService,
    )
    {
        $this->params = $params;
        $this->entityManager = $entityManager;
        $this->imageOptimizer = $imageOptimizer;
        $this->slugger = $slugger;
        $this->projectDir =  $this->params->get('app.projectDir');
        $this->photoDir =  $this->params->get('app.imgDir');
        $this->markdown = new MarkdownExtra();
        $this->markdownProcessor = $markdownProcessor;
        $this->urlGeneratorService = $urlGeneratorService;
    }

    #[Route('/', name: 'app_back_posts_index', methods: ['GET'])]
    public function index(PostsRepository $postsRepository, Request $request): Response
    {
        return $this->render('back/posts/index.html.twig', [
            'posts' => $postsRepository->findAll(),
        ]);
    }

    #[Route('/category/{name}', name: 'app_back_posts_list', methods: ['GET'])]
    public function categoryPage(PostsRepository $postsRepository, Category $category): Response
    {
        $posts = $postsRepository->findBy(['category' => $category]);
        return $this->render('back/posts/index.html.twig', [
            'posts' => $posts,
            'category' => $category,
        ]);
    }

    #[Route('/new', name: 'app_back_posts_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PostsRepository $postsRepository, MessageBusInterface $messageBus): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $post = new Posts();

        $category = new Category();

        $form = $this->createForm(PostsType::class, $post);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            // SLUG
            $slug = $this->slugger->slug($post->getTitle());
            if($post->getSlug() !== "Accueil") {
                $post->setSlug($slug);
                $categorySlug = $post->getCategory() ? $post->getCategory()->getSlug() : null;
                $subcategorySlug = $post->getSubcategory() ? $post->getSubcategory()->getSlug() : null;

                $url = $this->urlGeneratorService->generatePath($slug, $categorySlug, $subcategorySlug);
                $post->setUrl($url);
            } else {
                $post->setSlug('Accueil');
                $url = '';
                $post->setUrl($url);
            }


            // IMAGE Principal
            $brochureFile = $form->get('imgPost')->getData();
            if (empty($brochureFile)) {
                $post->setImgPost('Accueil');
                $post->setAltImg('Une Taupe Chez Vous ! image de présentation');
                $post->setImgWidth('1000');
                $post->setImgHeight('563');
            } else {
                $this->imageOptimizer->setPicture($brochureFile, $post, $slug);
            }

            // ALT IMG
            if (empty($post->getAltImg())) {
                $post->setAltImg($post->getTitle());
            } else {
                $post->setAltImg($post->getAltImg());
            }

            // DATE
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, null, 'dd MMMM yyyy');
            $post->setCreatedAt(new DateTime());
            $createdAt = $formatter->format($post->getCreatedAt());

            $post->setFormattedDate('Publié le ' . $createdAt);

            // MARKDOWN TO HTML
            $contentsText = $post->getContents();

            $htmlText = $this->markdownProcessor->processMarkdown($contentsText);

            $cleanedText = strip_tags($htmlText);
            $cleanedText = new UnicodeString($cleanedText);
            $cleanedText = $cleanedText->ascii();

            $post->setContents($cleanedText);

            $post->setContentsHTML($htmlText);

            // PARAGRAPH
            $paragraphPosts = $form->get('paragraphPosts')->getData();
            foreach ($paragraphPosts as $paragraph) {
                // SLUG
                $slugPara = $this->slugger->slug($paragraph->getSubtitle());
                $slugPara = substr($slugPara, 0, 30);

                if (!empty($paragraph->getSubtitle())) {
                    $paragraph->setSlug($slugPara);
                } else {
                    $this->entityManager->remove($paragraph);
                    $this->entityManager->flush();
                    }

                 // IMAGE PARAGRAPH
                 $brochureFileParagraph = $paragraph->getImgPost();

                 if (!empty($brochureFileParagraph)) {
                     // Cloudinary
                     $this->imageOptimizer->setPicture($paragraph,  $paragraph, $slugPara);
                 }

                 // ALT IMG PARAGRAPH
                 if (empty($paragraph->getAltImg())) {
                     $paragraph->setAltImg($paragraph->getSubtitle());
                 } else {
                     $paragraph->setAltImg($paragraph->getAltImg());
                 }
            }

            $message = new TriggerNextJsBuild('Build');
            $messageBus->dispatch($message);
            $buildResponse = $message->getContent();
            $postsRepository->save($post, true);
            return $this->render('some_template.html.twig', [
                'buildResponse' => $result,
            ]);

        }
        return $this->render('back/posts/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_back_posts_show', methods: ['GET'])]
    public function show(Posts $post): Response
    {
        return $this->render('back/posts/show.html.twig', [
            'post' => $post,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_back_posts_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Posts $post, $id, ParagraphPostsRepository $paragraphPostsRepository, PostsRepository $postsRepository, MessageBusInterface $messageBus): Response
    {
        $imgPost = $post->getImgPost();

        $articles = $postsRepository->findAll();
        $form = $this->createForm(PostsType::class, $post);
        $form->handleRequest($request);

        $paragraphPosts = $paragraphPostsRepository->find($id);


        $formParagraph = $this->createForm(ParagraphPostsType::class, $paragraphPosts);
        $formParagraph->handleRequest($request);

        $postExist = $postsRepository->find($id);

        if ($form->isSubmitted() && $form->isValid() ) {


            // SLUG
            $slug = $this->slugger->slug($post->getTitle());
            // if($post->getSlug() !== "Accueil") {
            //     $post->setSlug($slug);
            //     $categorySlug = $post->getCategory() ? $post->getCategory()->getSlug() : null;
            //     $subcategorySlug = $post->getSubcategory() ? $post->getSubcategory()->getSlug() : null;

            //     $url = $this->urlGeneratorService->generatePath($slug, $categorySlug, $subcategorySlug);
            //     $post->setUrl($url);
            // } else {
            //     $post->setSlug('Accueil');
            //     $url = '/';
            //     $post->setUrl($url);
            // }

            // IMAGE Principal
            $brochureFile = $form->get('imgPost')->getData();

            if (!empty($brochureFile)) {
                $this->imageOptimizer->setPicture($brochureFile, $post, $slug);
            } else {
                $post->setImgPost($imgPost);
            }

            // MARKDOWN TO HTML

            $dom = new DOMDocument();
            @$dom->loadHTML(
                mb_convert_encoding($post->getContents(), 'HTML-ENTITIES', 'UTF-8'),
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
            $images = $dom->getElementsByTagName('img');

            /** @var \DOMElement $image */
            foreach ($images as $image) {
                $image->setAttribute('loading', 'lazy');
            }

            $htmlTextWithLazyLoading = $dom->saveHTML();

            $post->setContents($htmlTextWithLazyLoading);
            // PARAGRAPH
            $paragraphPosts = $form->get('paragraphPosts')->getData();

            foreach ($paragraphPosts as $paragraph) {

                // MARKDOWN TO HTML
                $markdownText = $paragraph->getParagraph();


                $htmlText = $this->markdownProcessor->processMarkdown($markdownText);

                $dom = new DOMDocument('1.0', 'UTF-8');

                @$dom->loadHTML(
                    mb_convert_encoding($htmlText, 'HTML-ENTITIES', 'UTF-8'),
                    LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
                );

                $images = $dom->getElementsByTagName('img');

                /** @var \DOMElement $image */
                foreach ($images as $image) {
                    $image->setAttribute('loading', 'lazy');
                }

                $htmlTextWithLazyLoading = $dom->saveHTML();

                $paragraph->setParagraph($htmlTextWithLazyLoading);

                // LINK
                $articleLink = $paragraph->getLinkPostSelect();
                if ($articleLink !== null) {

                    $paragraph->setLinkSubtitle($articleLink->getTitle());
                    $slugLink = $articleLink->getSlug();

                    $categoryLink = $articleLink->getCategory()->getSlug();
                    if ($categoryLink === "Pages") {
                        $paragraph->setLink('/'.$slugLink);
                    }
                    if ($categoryLink === "Annuaire") {
                        $paragraph->setLink('/'.$categoryLink.'/'.$slugLink);
                    }
                    if ($categoryLink === "Articles") {
                        $subcategoryLink = $articleLink->getSubcategory()->getSlug();
                        $paragraph->setLink('/'.$categoryLink.'/'.$subcategoryLink.'/'.$slugLink);
                    }
                }



                // $deletedLink = $form['paragraphPosts'];

                // if ($deletedLink[$paragraphPosts->indexOf($paragraph)]['deleteLink']->getData() === true) {
                //     $paragraph->setLink(null);
                //     $paragraph->setLinkSubtitle(null);
                // }

                // SLUG
                if (!empty($paragraph->getSubtitle())) {
                    $slugPara = $this->slugger->slug($paragraph->getSubtitle());
                    $slugPara = substr($slugPara, 0, 30);
                    $paragraph->setSlug($slugPara);

                } else {
                    $this->entityManager->remove($paragraph);
                    $this->entityManager->flush();
                    }

                // IMAGE PARAGRAPH
                if (!empty($paragraph->getImgPostParaghFile())) {
                    $brochureFileParagraph = $paragraph->getImgPostParaghFile();
                    $slugPara = $this->slugger->slug($paragraph->getSubtitle());
                    $slugPara = substr($slugPara, 0, 30);
                    $paragraph->setImgPostParagh($slugPara);
                    $this->imageOptimizer->setPicture($brochureFileParagraph, $paragraph, $slugPara);

                    // ALT IMG PARAGRAPH
                    if (empty($paragraph->getAltImg())) {
                        $paragraph->setAltImg($paragraph->getSubtitle());
                    }
                }
            }

            $listPosts = $post->getListPosts();
            if ($listPosts !== null) {
                foreach ($listPosts as $listPost) {
                    if ($listPost->getLinkPostSelect() !== null){

                        $listPost->setLinkSubtitle($listPost->getLinkPostSelect()->getTitle());
                        $listPost->setLink($listPost->getLinkPostSelect()->getUrl());
                    }
                    if (empty($listPost->getTitle())) {
                        $this->entityManager->remove($listPost);
                        $this->entityManager->flush();
                    }
                }
            }

            // DATE
            $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, null, 'dd MMMM yyyy');
            $post->setUpdatedAt(new DateTime());
            $updatedDate = $formatter->format($post->getUpdatedAt());
            $createdAt = $formatter->format($post->getCreatedAt());

            $post->setFormattedDate('Publié le ' . $createdAt . '. Mise à jour le ' . $updatedDate);

            $postsRepository->save($post, true);

            $message = new TriggerNextJsBuild('Build');
            $messageBus->dispatch($message);
            $result = $message->getContent();
            return $this->redirectToRoute('app_back_posts_index', [
            ], Response::HTTP_SEE_OTHER);
        }
        $keyChatGpt = $_ENV['CHATGPT_API_KEY'];

        return $this->render('back/posts/edit.html.twig', [
            'post' => $post,
            'form' => $form,
            'articles' => $articles,
            'keyChatGpt' => $keyChatGpt,
         ]);
    }


    #[Route('/{id}', name: 'app_back_posts_delete', methods: ['POST'])]
    public function delete(Request $request, Posts $post, PostsRepository $postsRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $this->imageOptimizer->deletedPicture($post->getSlug());
            $postsRepository->remove($post, true);

        }

        return $this->redirectToRoute('app_back_posts_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/deleted', name: 'app_back_posts_paragraph_deleted', methods: ['GET', 'POST'])]
    public function deleteParagraph(Request $request, $id, PostsRepository $postsRepository, ParagraphPosts $paragraphPosts, ParagraphPostsRepository $paragraphPostsRepository): Response
    {

        $paragraph = $paragraphPostsRepository->find($id);

        $post = $postsRepository->find($id);
        $postId = $paragraph->getPosts()->getId();
        if ($this->isCsrfTokenValid('delete' . $paragraph->getId(), $request->request->get('_token'))) {
                $paragraph->setLink(null);
                $paragraph->setLinkSubtitle(null);

                $this->imageOptimizer->deletedPicture($slug);

                $this->entityManager->flush();

        }

        return $this->redirectToRoute('app_back_posts_edit', ['id' => $postId], Response::HTTP_SEE_OTHER);
    }


}
