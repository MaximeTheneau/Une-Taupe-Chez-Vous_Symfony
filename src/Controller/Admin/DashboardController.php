<?php

namespace App\Controller\Admin;

use App\Entity\Posts;
use App\Entity\ListPosts;
use App\Entity\Category;
use App\Entity\Subcategory;
use App\Entity\Subtopic;
use App\Entity\Skill;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Comments;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(PostsCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Portfolio - Administration')
            ->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::subMenu('Contenu', 'fa fa-newspaper')->setSubItems([
            MenuItem::linkTo( PostsCrudController::class, 'Posts', 'fa fa-newspaper'),
            MenuItem::linkTo(CommentsCrudController::class, 'Commentaires', 'fa fa-comments'),
        ]);

        yield MenuItem::subMenu('Taxonomie', 'fa fa-tags')->setSubItems([
            MenuItem::linkTo(CategoryCrudController::class, 'Categories', 'fa fa-folder'),
            MenuItem::linkTo(SubcategoryCrudController::class, 'Subcategories', 'fa fa-folder-open'),
            // MenuItem::linkTo(SubtopicCrudController::class, 'Subtopics', 'fa fa-tag'),
        ]);

        yield MenuItem::linkTo(SkillCrudController::class, 'Skills', 'fa fa-cogs');
        yield MenuItem::linkTo(ProductCrudController::class, 'Products', 'fa fa-shopping-cart');
        yield MenuItem::linkTo(UserCrudController::class, 'Users', 'fa fa-users');
    }
}
