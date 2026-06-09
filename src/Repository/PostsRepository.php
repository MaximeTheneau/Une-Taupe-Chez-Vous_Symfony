<?php

namespace App\Repository;

use App\Entity\Posts;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

/**
 * @extends ServiceEntityRepository<Posts>
 *
 * @method Posts|null find($id, $lockMode = null, $lockVersion = null)
 * @method Posts|null findOneBy(array $criteria, array $orderBy = null)
 * @method Posts[]    findAll()
 * @method Posts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostsRepository extends ServiceEntityRepository
{


    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Posts::class);
    }

    public function save(Posts $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Posts $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
    public function findAllPosts()
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.draft = false')
            ->orderBy('CASE WHEN r.updatedAt IS NOT NULL THEN r.updatedAt ELSE r.createdAt END', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findLastPosts()
    {
        return $this->createQueryBuilder('r')
            ->orderBy('CASE WHEN r.updatedAt IS NOT NULL THEN r.updatedAt ELSE r.createdAt END', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }
    public function findByCategorySlug(string $slug, int $limit)
    {
        $homePageSlug = 'Accueil';

        return $this->createQueryBuilder('p')
            ->andWhere('p.draft = false')
            ->join('p.category', 'c')
            ->andWhere('c.slug = :slug')
            ->andWhere('p.slug != :homePageSlug')
            ->setParameter('slug', $slug)
            ->setParameter('homePageSlug', $homePageSlug)
            ->orderBy('CASE WHEN p.updatedAt IS NOT NULL THEN p.updatedAt ELSE p.createdAt END', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        }

    public function findAllNonDraftPosts(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.draft IS NULL OR p.draft = :draft')
            ->setParameter('draft', false, \Doctrine\DBAL\Types\Type::BOOLEAN) // Spécifiez le type du paramètre
            ->getQuery()
            ->getResult();
    }

    public function findAllPostsExcludingSlugs(array $excludeSlugs): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.draft = false')
            ->andWhere('p.slug NOT IN (:excludeSlugs)') // Exclude specified slugs
            ->setParameter('excludeSlugs', $excludeSlugs)
            ->orderBy('CASE WHEN p.updatedAt IS NOT NULL THEN p.updatedAt ELSE p.createdAt END', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
