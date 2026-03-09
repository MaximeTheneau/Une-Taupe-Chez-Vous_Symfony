<?php

namespace App\Controller\Admin;

use App\Entity\ListPosts;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\EntityManagerInterface;

class ListPostsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ListPosts::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Élément de liste')
            ->setEntityLabelInPlural('Listes de liens')
            ->setSearchFields(['title', 'linkSubtitle'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        if ($pageName !== Crud::PAGE_EDIT && $pageName !== Crud::PAGE_NEW || !$this->isEmbedded()) {
            yield AssociationField::new('posts', 'Post parent')
                ->setRequired(true)
                ->setColumns(12);
        }

        yield TextField::new('title', 'Titre')
            ->setColumns(6);

        yield TextareaField::new('description', 'Description')
            ->setRequired(false)
            ->setColumns(12);

        // yield AssociationField::new('linkPostSelect', 'Lien vers un article')
        //     ->setRequired(false)
        //     ->setHelp('Remplit automatiquement le lien et le titre du lien')
        //     ->setColumns(6);

        yield TextField::new('linkSubtitle', 'Titre du lien (généré)')
            ->hideOnForm()
            ->setColumns(6);

        yield TextField::new('link', 'URL (générée)')
            ->hideOnForm()
            ->setColumns(6);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ListPosts) {
            return;
        }

        $this->processListPost($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ListPosts) {
            return;
        }

        $this->processListPost($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function processListPost(ListPosts $listPost): void
    {
        $articleLink = $listPost->getLinkPostSelect();
        if ($articleLink !== null) {
            $listPost->setLinkSubtitle($articleLink->getTitle());
            $listPost->setLink($articleLink->getUrl());
        }
    }

    private function isEmbedded(): bool
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        return $request && $request->query->has('referrer');
    }
}
