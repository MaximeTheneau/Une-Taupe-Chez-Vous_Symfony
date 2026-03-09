<?php

namespace App\Controller\Admin;

use App\Entity\Subtopic;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class SubtopicCrudController extends AbstractCrudController
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public static function getEntityFqcn(): string
    {
        return Subtopic::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Subtopic')
            ->setEntityLabelInPlural('Subtopics')
            ->setSearchFields(['name', 'slug']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Subtopic) {
            return;
        }

        if (empty($entityInstance->getSlug())) {
            $slug = strtolower($this->slugger->slug($entityInstance->getName())->slice(0, 50)->toString());
            $entityInstance->setSlug($slug);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Subtopic) {
            return;
        }

        $slug = strtolower($this->slugger->slug($entityInstance->getName())->slice(0, 50)->toString());
        $entityInstance->setSlug($slug);

        parent::updateEntity($entityManager, $entityInstance);
    }
}
