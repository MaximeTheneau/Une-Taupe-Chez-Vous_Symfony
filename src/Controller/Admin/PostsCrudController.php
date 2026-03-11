<?php

namespace App\Controller\Admin;

use App\Entity\Posts;
use App\Entity\ParagraphPosts;
use App\Message\TriggerNextJsBuild;
use App\Service\ImageOptimizer;
use App\Service\UrlGeneratorService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use App\Entity\PostLogEntry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use DateTime as GlobalDateTime;
use IntlDateFormatter;
use DOMDocument;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PostsCrudController extends AbstractCrudController
{
    private SluggerInterface $slugger;
    private ImageOptimizer $imageOptimizer;
    private UrlGeneratorService $urlGeneratorService;
    private MessageBusInterface $messageBus;
    private EntityManagerInterface $entityManager;
    private AdminUrlGenerator $adminUrlGenerator;
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(
        SluggerInterface $slugger,
        ImageOptimizer $imageOptimizer,
        UrlGeneratorService $urlGeneratorService,
        MessageBusInterface $messageBus,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
    ) {
        $this->slugger = $slugger;
        $this->imageOptimizer = $imageOptimizer;
        $this->urlGeneratorService = $urlGeneratorService;
        $this->messageBus = $messageBus;
        $this->entityManager = $entityManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public static function getEntityFqcn(): string
    {
        return Posts::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('trix-upload');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Post')
            ->setEntityLabelInPlural('Posts')
            ->setSearchFields(['title', 'heading', 'slug', 'metaDescription'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $historyAction = Action::new('showHistory', 'Historique', 'fa fa-history')
            ->linkToRoute('admin_posts_history', static fn (Posts $post): array => ['id' => $post->getId()])
            ->addCssClass('btn btn-secondary btn-sm');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $historyAction)
            ->add(Crud::PAGE_EDIT, $historyAction);
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_INDEX) {
            yield IdField::new('id')->hideOnForm();
            yield TextField::new('title', 'Titre');
            yield AssociationField::new('category', 'Catégorie');
            yield TextField::new('imgPost', 'Image')->setTemplatePath('admin/fields/image_url.html.twig');
            yield DateTimeField::new('createdAt', 'Date de création');
            yield DateTimeField::new('updatedAt', 'Mise à jour');
            return;
        }

        yield TextField::new('title', 'Titre (meta title)')
            ->setFormTypeOption('attr.maxlength', 70)
            ->setHelp('Max 70 caractères')
            ->setColumns(6);
        yield TextField::new('heading', 'En-tête (h1)')
            ->setFormTypeOption('attr.maxlength', 65)
            ->setHelp('Max 65 caractères')
            ->setColumns(6);
        yield TextareaField::new('metaDescription', 'Meta Description')
            ->setFormTypeOption('attr.maxlength', 160)
            ->setHelp('Max 160 caractères')
            ->setColumns(12);

        yield TextField::new('slug', 'Slug')
            ->setFormTypeOption('attr.maxlength', 70)
            ->setHelp('Laissez vide pour générer automatiquement — Max 70 caractères')
            ->setColumns(6);

        yield TextEditorField::new('contents', 'Contenu')
            ->setColumns(12);

        yield AssociationField::new('category', 'Catégorie')->setColumns(6);
        yield AssociationField::new('subcategory', 'Sous-catégorie')->setColumns(6);
        yield AssociationField::new('keywords', 'Mots-clés')->setColumns(12);

        // ── Image — affichage index/détail ────────────────────────────────────
        yield ImageField::new('img', 'Logo')
            ->hideOnForm();

        // ── Image — formulaire (upload + suppression) ─────────────────────────
        yield Field::new('imageFile', 'Nouveau logo (jpg, png, avif, webp — max 100×100)')
            ->setFormType(FileType::class)
            ->onlyOnForms()
            ->setFormTypeOptions([
                'required' => false,
                'mapped'   => true,
                'attr'     => ['accept' => 'image/jpeg,image/png,image/avif,image/webp'],
            ]);

        yield Field::new('deleteImage', 'Supprimer le logo actuel')
            ->onlyOnForms()
            ->setFormType(CheckboxType::class)
            ->setFormTypeOptions(['required' => false, 'mapped' => true])
            ->hideWhenCreating();

        yield TextField::new('altImg', 'Alt Image')
            ->setHelp('Laissez vide pour utiliser le titre')
            ->setColumns(6);


        yield BooleanField::new('isHomeImage', 'Afficher sur la page d\'accueil')
            ->setColumns(6);

        yield CollectionField::new('paragraphPosts', 'Paragraphes')
            ->useEntryCrudForm(ParagraphPostsCrudController::class)
            ->setColumns(12);

        yield CollectionField::new('listPosts', 'Liste de liens')
            ->useEntryCrudForm(ListPostsCrudController::class)
            ->setColumns(12);

        yield AssociationField::new('relatedPosts', 'Posts associés')
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->setColumns(12);


        if ($pageName === Crud::PAGE_DETAIL) {
            yield DateTimeField::new('createdAt', 'Date de création');
            yield DateTimeField::new('updatedAt', 'Date de mise à jour');
            yield TextField::new('url', 'URL générée');
            yield TextField::new('formattedDate', 'Date formatée');
            yield IntegerField::new('imgWidth', 'Largeur image');
            yield IntegerField::new('imgHeight', 'Hauteur image');
            yield TextField::new('srcset', 'Srcset');
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Posts) {
            return;
        }

        $this->processPost($entityInstance, true);
        $this->handleImageUpload($entityInstance);

        $this->messageBus->dispatch(new TriggerNextJsBuild('Build'));

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Posts && $entityInstance->getSlug()) {
            $this->imageOptimizer->clearImage($entityInstance->getImgPost());
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    private function handleImageDeletion(Posts $post): void
    {
        if (!$post->isDeleteImage() || $post->getImgPost() === null) {
            return;
        }

        $this->imageOptimizer->clearImage($post->getImgPost());
        $post->setImgPost(null)->setSrcset(null)->setImgWidth(null)->setImgHeight(null);
    }
    private function handleImageUpload(Posts $post): void
    {
        $file = $post->getImageFile();
        if (!$file instanceof UploadedFile) {
            return;
        }

        try {
            $slug = $post->getSlug() ?? uniqid('post_');
            $this->imageOptimizer->setPicture($file, $post, $slug);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
        }
    }
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Posts) {
            return;
        }

        $this->processPost($entityInstance, false);
        $this->handleImageDeletion($entityInstance);
        $this->handleImageUpload($entityInstance);

        $this->messageBus->dispatch(new TriggerNextJsBuild('Build'));

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function processPost(Posts $post, bool $isNew): void
    {
        // SLUG
        if (empty($post->getSlug()) || $post->getSlug() !== 'Accueil') {
            if (empty($post->getSlug())) {
                $slug = $this->createSlug($post->getTitle());
                $post->setSlug($slug);
            }
        }

        $slug = $post->getSlug();

        // URL
        if ($slug !== 'Accueil') {
            $categorySlug = $post->getCategory() ? $post->getCategory()->getSlug() : null;
            $subcategorySlug = $post->getSubcategory() ? $post->getSubcategory()->getSlug() : null;
            $url = $this->urlGeneratorService->generatePath($slug, $categorySlug, $subcategorySlug);
            $post->setUrl($url);
        } else {
            $post->setUrl('');
        }

        // ALT IMG
        if (empty($post->getAltImg())) {
            $post->setAltImg($post->getTitle());
        }

        // Lazy loading images dans le contenu HTML (TextEditorField produit du HTML)
        if (!empty($post->getContents())) {
            $htmlText = $post->getContents();

            $dom = new DOMDocument();
            @$dom->loadHTML(
                mb_convert_encoding($htmlText, 'HTML-ENTITIES', 'UTF-8'),
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
            $images = $dom->getElementsByTagName('img');

            foreach ($images as $image) {
                $image->setAttribute('loading', 'lazy');
            }

            $htmlTextWithLazyLoading = $dom->saveHTML();
            $post->setContents($htmlTextWithLazyLoading);
            $post->setContentsHTML($htmlTextWithLazyLoading);
        }

        // DATE
        $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, null, 'dd MMMM yyyy');

        if ($isNew) {
            $post->setCreatedAt(new GlobalDateTime());
            $createdAt = $formatter->format($post->getCreatedAt());
            $post->setFormattedDate('Publié le ' . $createdAt);
        } else {
            $post->setUpdatedAt(new GlobalDateTime());
            $updatedDate = $formatter->format($post->getUpdatedAt());
            $createdAt = $formatter->format($post->getCreatedAt());
            $post->setFormattedDate('Publié le ' . $createdAt . '. Mise à jour le ' . $updatedDate);
        }

        // PARAGRAPHS
        foreach ($post->getParagraphPosts() as $paragraph) {
            $this->processParagraph($paragraph, $post);
        }

        // LIST POSTS
        foreach ($post->getListPosts() as $listPost) {
            if (empty($listPost->getTitle())) {
                $this->entityManager->remove($listPost);
                continue;
            }

            $articleLink = $listPost->getLinkPostSelect();
            if ($articleLink !== null) {
                $listPost->setLinkSubtitle($articleLink->getTitle());
                $listPost->setLink($articleLink->getUrl());
            }
        }
    }

    private function processParagraph(ParagraphPosts $paragraph, Posts $post): void
    {
        // Lazy loading images dans le paragraphe HTML
        if (!empty($paragraph->getParagraph())) {
            $htmlText = $paragraph->getParagraph();

            $dom = new DOMDocument('1.0', 'UTF-8');
            @$dom->loadHTML(
                mb_convert_encoding($htmlText, 'HTML-ENTITIES', 'UTF-8'),
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            $images = $dom->getElementsByTagName('img');
            foreach ($images as $image) {
                $image->setAttribute('loading', 'lazy');
            }

            $htmlTextWithLazyLoading = $dom->saveHTML();
            $paragraph->setParagraph($htmlTextWithLazyLoading);
        }

        // SLUG
        if (!empty($paragraph->getSubtitle())) {
            $slugPara = $this->createSlug($paragraph->getSubtitle());
            $slugPara = substr($slugPara, 0, 30);
            $paragraph->setSlug($slugPara);

            $categoryLink = $post->getCategory() ? $post->getCategory()->getSlug() : null;
            if ($categoryLink === 'Pages') {
                $paragraph->setLinkSubtitle('/' . $slugPara);
            } else {
                $paragraph->setLinkSubtitle('/' . $categoryLink . '/' . $slugPara);
            }
        } else {
            $this->entityManager->remove($paragraph);
        }

        // ALT IMG
        if (empty($paragraph->getAltImg()) && !empty($paragraph->getSubtitle())) {
            $paragraph->setAltImg($paragraph->getSubtitle());
        }

        // LINK depuis linkPostSelect
        $articleLink = $paragraph->getLinkPostSelect();
        if ($articleLink !== null) {
            $paragraph->setLinkSubtitle($articleLink->getTitle());
            $slugLink = $articleLink->getSlug();
            $categoryLink = $articleLink->getCategory() ? $articleLink->getCategory()->getSlug() : null;

            if ($categoryLink === 'Pages') {
                $paragraph->setLink('/' . $slugLink);
            } elseif ($categoryLink === 'Annuaire') {
                $paragraph->setLink('/' . $categoryLink . '/' . $slugLink);
            } elseif ($categoryLink === 'Articles') {
                $subcategoryLink = $articleLink->getSubcategory() ? $articleLink->getSubcategory()->getSlug() : null;
                $paragraph->setLink('/' . $categoryLink . '/' . $subcategoryLink . '/' . $slugLink);
            }
        } else {
            $paragraph->setLink(null);
            $paragraph->setLinkSubtitle(null);
        }
    }

    #[Route('/admin/posts/{id}/history', name: 'admin_posts_history')]
    public function showHistory(Request $request): Response
    {
        // Résout l'id depuis la route Symfony directe OU depuis le param EA (entityId)
        $id = $request->attributes->get('id') ?? $request->query->get('entityId');
        $post = $this->entityManager->getRepository(Posts::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found.');
        }

        $repo = $this->entityManager->getRepository(PostLogEntry::class);

        $postLogs = $repo->findBy(
            ['objectClass' => Posts::class, 'objectId' => (string) $post->getId()],
            ['version' => 'DESC']
        );

        $paragraphLogs = [];
        foreach ($post->getParagraphPosts() as $paragraph) {
            if ($paragraph->getId()) {
                $logs = $repo->findBy(
                    ['objectClass' => ParagraphPosts::class, 'objectId' => (string) $paragraph->getId()],
                    ['version' => 'DESC']
                );
                $paragraphLogs[$paragraph->getId()] = [
                    'subtitle' => $paragraph->getSubtitle(),
                    'logs' => $logs,
                ];
            }
        }

        $detailUrl = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($post->getId())
            ->generateUrl();

        return $this->render('admin/posts/history.html.twig', [
            'post' => $post,
            'postLogs' => $postLogs,
            'paragraphLogs' => $paragraphLogs,
            'detailUrl' => $detailUrl,
        ]);
    }

    #[Route('/admin/posts/{id}/revert/{version}', name: 'admin_posts_revert', methods: ['POST'])]
    public function revertPost(int $id, int $version, Request $request): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('revert_post_' . $id, $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $post = $this->entityManager->getRepository(Posts::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Post introuvable.');
        }

        $repo = $this->entityManager->getRepository(PostLogEntry::class);
        $repo->revert($post, $version);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Post restauré à la version %d.', $version));

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::EDIT)
                ->setEntityId($id)
                ->generateUrl()
        );
    }

    #[Route('/admin/paragraphs/{id}/revert/{version}', name: 'admin_paragraph_revert', methods: ['POST'])]
    public function revertParagraph(int $id, int $version, Request $request): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('revert_paragraph_' . $id, $request->request->get('_token')))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $paragraph = $this->entityManager->getRepository(ParagraphPosts::class)->find($id);

        if (!$paragraph) {
            throw $this->createNotFoundException('Paragraphe introuvable.');
        }

        $postId = $paragraph->getPosts()?->getId();

        $repo = $this->entityManager->getRepository(PostLogEntry::class);
        $repo->revert($paragraph, $version);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Paragraphe restauré à la version %d.', $version));

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::EDIT)
                ->setEntityId($postId)
                ->generateUrl()
        );
    }

    private function createSlug(string $inputString): string
    {
        return strtolower($this->slugger->slug($inputString)->slice(0, 50)->toString());
    }
}
