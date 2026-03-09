<?php

namespace App\Controller\Admin;

use App\Entity\ParagraphPosts;
use App\Service\ImageOptimizer;
use App\Service\MarkdownProcessor;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\String\Slugger\SluggerInterface;
use DOMDocument;

class ParagraphPostsCrudController extends AbstractCrudController
{
    private SluggerInterface $slugger;
    private MarkdownProcessor $markdownProcessor;
    private ImageOptimizer $imageOptimizer;
    private string $projectDir;

    public function __construct(
        SluggerInterface $slugger,
        MarkdownProcessor $markdownProcessor,
        ImageOptimizer $imageOptimizer,
        string $projectDir
    ) {
        $this->slugger = $slugger;
        $this->markdownProcessor = $markdownProcessor;
        $this->imageOptimizer = $imageOptimizer;
        $this->projectDir = $projectDir;
    }

    public static function getEntityFqcn(): string
    {
        return ParagraphPosts::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Paragraphe')
            ->setEntityLabelInPlural('Paragraphes')
            ->setSearchFields(['subtitle', 'slug'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // Masquer le champ posts dans le formulaire embarqué (CollectionField)
        if ($pageName !== Crud::PAGE_EDIT && $pageName !== Crud::PAGE_NEW || !$this->isEmbedded()) {
            yield AssociationField::new('posts', 'Post')
                ->setRequired(true)
                ->setColumns(12);
        }

        yield TextField::new('subtitle', 'Sous-titre')
            ->setHelp('Max 170 caractères')
            ->setColumns(12);

        yield TextEditorField::new('paragraph', 'Contenu')
            ->setColumns(12);

        yield ImageField::new('imgPost', 'Image du paragraphe')
            ->setBasePath('')
            ->setUploadDir('public/uploads/tmp/')
            ->setUploadedFileNamePattern('[timestamp]-[slug].[extension]')
            ->setRequired(false)
            ->hideOnIndex()
            ->setColumns(6);

        yield TextField::new('altImg', 'Alt Image')
            ->hideOnIndex()
            ->setColumns(6);

        yield AssociationField::new('linkPostSelect', 'Lien vers un article')
            ->setRequired(false)
            ->hideOnIndex()
            ->setColumns(6);

        yield TextField::new('slug', 'Slug')
            ->hideOnForm()
            ->setColumns(6);

        yield TextField::new('linkSubtitle', 'Lien généré')
            ->hideOnForm()
            ->setColumns(6);
    }

    private function isEmbedded(): bool
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        return $request && $request->query->has('referrer');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ParagraphPosts) {
            return;
        }

        $this->processParagraph($entityInstance);
        $this->processParagraphImage($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ParagraphPosts) {
            return;
        }

        $this->processParagraph($entityInstance);
        $this->processParagraphImage($entityInstance, $entityManager);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function processParagraphImage(ParagraphPosts $paragraph, ?EntityManagerInterface $entityManager = null): void
    {
        $imgPost = $paragraph->getImgPost();

        if (empty($imgPost) || str_starts_with($imgPost, 'http')) {
            if (empty($imgPost) && $entityManager && $paragraph->getId()) {
                $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($paragraph);
                if (!empty($originalData['imgPost'])) {
                    $paragraph->setImgPost($originalData['imgPost']);
                }
            }
            return;
        }

        $localPath = $this->projectDir . '/public/uploads/tmp/' . $imgPost;
        if (file_exists($localPath)) {
            $file = new File($localPath);
            $slug = $paragraph->getSlug() ?? $this->createSlug($paragraph->getSubtitle() ?? 'paragraph');
            $this->imageOptimizer->setPicture($file, $paragraph, $slug);
        }
    }

    private function processParagraph(ParagraphPosts $paragraph): void
    {
        // Lazy loading images dans le paragraphe HTML (TextEditorField produit du HTML)
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

        // SLUG Generation for paragraph
        if (!empty($paragraph->getSubtitle())) {
            $slugPara = $this->createSlug($paragraph->getSubtitle());
            $slugPara = substr($slugPara, 0, 30);
            $paragraph->setSlug($slugPara);

            // Generate link if post is attached
            if ($paragraph->getPosts()) {
                $categoryLink = $paragraph->getPosts()->getCategory() ? $paragraph->getPosts()->getCategory()->getSlug() : null;
                if ($categoryLink === 'Pages') {
                    $paragraph->setLinkSubtitle('/' . $slugPara);
                } else {
                    $paragraph->setLinkSubtitle('/' . $categoryLink . '/' . $slugPara);
                }
            }
        }

        // ALT IMG for paragraph
        if (empty($paragraph->getAltImg()) && !empty($paragraph->getSubtitle())) {
            $paragraph->setAltImg($paragraph->getSubtitle());
        }
    }

    private function createSlug(string $inputString): string
    {
        return strtolower($this->slugger->slug($inputString)->slice(0, 50)->toString());
    }
}
