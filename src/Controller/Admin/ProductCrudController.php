<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\EntityManagerInterface;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Product')
            ->setEntityLabelInPlural('Products')
            ->setSearchFields(['name']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom');
        yield TextEditorField::new('description', 'Description')
            ->hideOnIndex();
        yield TextField::new('price', 'Prix');
        yield TextField::new('discountedPrice', 'Prix réduit')->hideOnForm();
        yield UrlField::new('url', 'URL');
        yield AssociationField::new('productOptions', 'Options')
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->setColumns(12);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            return;
        }

        $this->processProduct($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            return;
        }

        $this->processProduct($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function processProduct(Product $product): void
    {
        // Discounted price = 50% of price
        if (!empty($product->getPrice())) {
            $product->setDiscountedPrice((string)($product->getPrice() * 0.5));
        }
    }
}
