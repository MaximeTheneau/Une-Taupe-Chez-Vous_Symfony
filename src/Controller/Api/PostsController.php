<?php

namespace App\Controller\Api;

use App\Entity\Posts;
use App\Entity\Category;
use App\Entity\Subcategory;
use App\Repository\CommentsRepository;
use App\Repository\PostsRepository;
use App\Repository\SubcategoryRepository;
use App\Repository\KeywordRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/posts', name: 'api_posts_')]
class PostsController extends ApiController
{

    #[Route('/home', name: 'home', methods: ['GET'])]
    public function home(PostsRepository $postsRepository, EntityManagerInterface $em ): JsonResponse
    {

        $home = $postsRepository->findBy(['slug' => 'Accueil']);
        $interventions = $postsRepository->findByCategorySlug('Interventions', 3);
        $testimonials = $postsRepository->findBy(['slug' => 'Temoignages']);
        $category = $em->getRepository(Category::class)->findByName('Articles');
        $blog = $em->getRepository(Posts::class)->findBy(['category' => $category, 'isHomeImage' => true], ['createdAt' => 'DESC'], 3);

        return $this->json(
            [
                'home' =>  $home[0],
                'interventions' => $interventions,
                'testimonials' => $testimonials[0],
                'blog'=> $blog,

            ],
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_home"
                ]
            ]
        );
    }

    #[Route('&category={slug}', name: 'articles', methods: ['GET'])]
    public function category(PostsRepository $postsRepository, #[MapEntity(mapping: ['slug' => 'slug'])] Category $category): JsonResponse
    {
        $posts = $postsRepository->findBy(['category' => $category, 'draft' => false], ['createdAt' => 'DESC']);

        return $this->json(
            $posts,
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_category"

                ]
            ]
        );
    }

    #[Route('&subcategory={slug}', name: 'subcategory', methods: ['GET'])]
    public function subcategory(PostsRepository $postsRepository, #[MapEntity(mapping: ['slug' => 'slug'])] Subcategory $subcategory): JsonResponse
    {
        $posts = $postsRepository->findBy(['subcategory' => $subcategory, 'draft' => false],  ['createdAt' => 'DESC']);

        return $this->json(
            $posts,
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_subcategory"

                ]
            ]
        );
    }

    #[Route('&limit=3&category={name}', name: 'category', methods: ['GET'])]
    public function limit(PostsRepository $postsRepository, #[MapEntity(mapping: ['name' => 'name'])] Category $category): JsonResponse
    {
        $posts = $postsRepository->findBy(['category' => $category, 'draft' => false], ['createdAt' => 'ASC'], 3);


        return $this->json(
            $posts,
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_category"

                ]
            ]
        );
    }

    #[Route('&limit=3&filter=desc&category={slug}', name: 'desc', methods: ['GET'])]
    public function desc(PostsRepository $postsRepository, string $slug ): JsonResponse
    {

        $posts = $postsRepository->findByCategorySlug($slug, 3);


        return $this->json(
            $posts,
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_desc"
                ]
            ]
        );
    }

    #[Route('/all', name: 'all', methods: ['GET'])]
    public function all(PostsRepository $postsRepository ): JsonResponse
    {

        $allPosts = $postsRepository->findAllPosts();


        return $this->json(
            $allPosts,
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_all"
                ]
            ]
        );
    }

    #[Route('/sitemap', name: 'sitemap', methods: ['GET'])]
    public function sitemap(PostsRepository $postsRepository ): JsonResponse
    {

        $excludeSlugs = ['search'];

        $allPosts = $postsRepository->findAllPostsExcludingSlugs($excludeSlugs);

        return $this->json(
            $allPosts,
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_sitemap"
                ]
            ]
        );
    }

    #[Route('/thumbnail/{slug}', name: 'thumbnail', methods: ['GET'])]
    public function thumbnail(PostsRepository $postsRepository, #[MapEntity(mapping: ['slug' => 'slug'])] Posts $posts = null ): JsonResponse
    {

        if ($posts === null)
        {
            // on renvoie donc une 404
            return $this->json(
                [
                    "erreur" => "Page non trouvée",
                    "code_error" => 404
                ],
                Response::HTTP_NOT_FOUND,// 404
            );
        }

        return $this->json(
            $posts,
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_thumbnail"
                ]
            ]
        );
    }

    #[Route('/{slug}', name: 'read', methods: ['GET'])]
    public function read(EntityManagerInterface $em, string $slug)
    {

        $post = $em->getRepository(Posts::class)->findOneBy(['slug' => $slug]);

        return $this->json(
            $post,
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_read"
                ]
            ]);
    }

     #[Route('/blog/{slug}', name: 'readBlog', methods: ['GET'])]
    public function readArticles(EntityManagerInterface $em, #[MapEntity(mapping: ['slug' => 'slug'])] Posts $post, CommentsRepository $commentRepository)
    {
        $comments = $commentRepository->findNonReplyComments($post->getId());

        $commentsCollection = new ArrayCollection($comments);

        $post->setComments($commentsCollection);

        if ($post === null)
        {
            // on renvoie donc une 404
            return $this->json(
                [
                    "erreur" => "Page non trouvée",
                    "code_error" => 404
                ],
                Response::HTTP_NOT_FOUND,// 404
            );
        }
        $latestPosts = $em->getRepository(Posts::class)->findBy([], ['createdAt' => 'DESC'], 3);

        $relatedPosts = $post->getRelatedPosts();

            // Si vous voulez seulement certaines données des posts associés, vous pouvez mapper les entités en un tableau d'objets plus simples
            $relatedPostsData = [];
            foreach ($relatedPosts as $relatedPost) {
                $relatedPostsData[] = [
                    'id' => $relatedPost->getId(),
                    'slug' => $relatedPost->getSlug(),
                    'title' => $relatedPost->getTitle(),
                    'altImg' => $relatedPost->getAltImg(),
                    'url' => $relatedPost->getUrl(),
                    'imgPost' => $relatedPost->getImgPost(),

                ];
            }

            $latestPostsData = [];
            foreach ($latestPosts as $latestPost) {
                $latestPostsData[] = [
                    'id' => $latestPost->getId(),
                    'slug' => $latestPost->getSlug(),
                    'title' => $latestPost->getTitle(),
                    'url' => $latestPost->getUrl(),
                    'formattedDate' => $latestPost->getFormattedDate(),

                ];
            }
        return $this->json(
            [
                'post' => $post,
                'latestPosts' => $latestPostsData,
                'relatedPosts' => $relatedPostsData
            ],
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_read",
                ]
            ]);
    }


    #[Route('&filter=subcategory', name: 'allSubcategory', methods: ['GET'])]
    public function allSubcategory(SubcategoryRepository $subcategories ): JsonResponse
    {

        $subcategories = $subcategories->findAll();

        return $this->json(
            $subcategories,
            Response::HTTP_OK,
            [],
            [
                "groups" => ["api_posts__allSubcategory"]
            ]
        );
    }

    #[Route('&filter=keyword&limit=3&id={id}', name: 'keyword', methods: ['GET'])]
    public function postsFilterKeyword(PostsRepository $postsRepository, int $id): JsonResponse
    {
        $post = $postsRepository->find($id);
        $postId = $post->getId();
        $postsKeyword = $post->getKeywords()->getValues();

        if ($postsKeyword === []) {
            $posts = $postsRepository->findByCategorySlug($post->getCategory()->getSlug(), 3);
            return $this->json($this->mapPostsToArray($posts));
        }

        $filteredPosts = [];
        $addedPostIds = [];

        foreach ($postsKeyword as $keyword) {
            $keywordPosts = $keyword->getPosts();
            $filteredPostId = $keywordPosts->filter(function ($otherPost) use ($postId) {
                return $otherPost->getId() != $postId && !$otherPost->isDraft() && $otherPost->getSlug() !== 'Accueil';
            });

            foreach ($filteredPostId as $filteredPost) {
                $filteredPostObjectId = $filteredPost->getId();
                if (!in_array($filteredPostObjectId, $addedPostIds)) {
                    $filteredPosts[] = $filteredPost;
                    $addedPostIds[] = $filteredPostObjectId;
                }
            }
        }

        usort($filteredPosts, function ($a, $b) {
            $updatedAtA = $a->getUpdatedAt();
            $updatedAtB = $b->getUpdatedAt();

            if ($updatedAtA && $updatedAtB) {
                return $updatedAtB <=> $updatedAtA;
            } elseif ($updatedAtA && !$updatedAtB) {
                return -1;
            } elseif (!$updatedAtA && $updatedAtB) {
                return 1;
            } else {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            }
        });

        if (count($filteredPosts) >= 3) {
            $posts = array_slice($filteredPosts, 0, 3);
        } else {
            $posts = $postsRepository->findByCategorySlug($post->getCategory()->getSlug(), 3);
        }

        return $this->json($this->mapPostsToArray($posts));
    }

    private function mapPostsToArray(array $posts): array
    {
        return array_map(fn($p) => [
            'id'          => $p->getId(),
            'title'       => $p->getTitle(),
            'slug'        => $p->getSlug(),
            'altImg'      => $p->getAltImg(),
            'imgPost'     => $p->getImgPost(),
            'url'         => $p->getUrl(),
            'category'    => $p->getCategory() ? ['name' => $p->getCategory()->getName()] : null,
            'subcategory' => $p->getSubcategory() ? ['name' => $p->getSubcategory()->getName(), 'slug' => $p->getSubcategory()->getSlug()] : null,
        ], $posts);
    }

#[Route('/related/{slug}', name: 'related', methods: ['GET'])]
    public function relatedPosts(EntityManagerInterface $em, string $slug )
    {
        $post = $em->getRepository(Posts::class)->findOneBy(['slug' => $slug]);

        if ($post === null)
        {
            // on renvoie donc une 404
            return $this->json(
                [
                    "erreur" => "Page non trouvée",
                    "code_error" => 404
                ],
                Response::HTTP_NOT_FOUND,// 404
            );
        }
    $relatedPosts = $post->getRelatedPosts();

    // Si vous voulez seulement certaines données des posts associés, vous pouvez mapper les entités en un tableau d'objets plus simples
    $relatedPostsData = [];
    foreach ($relatedPosts as $relatedPost) {
        $relatedPostsData[] = [
            'id' => $relatedPost->getId(),
            'slug' => $relatedPost->getSlug(),
            'title' => $relatedPost->getTitle(),
            'altImg' => $relatedPost->getAltImg(),
            'url' => $relatedPost->getUrl(),
            'imgPost' => $relatedPost->getImgPost(),

        ];
    }

    // Retourner la réponse JSON avec les posts associés
    return $this->json(
        $relatedPostsData,
            Response::HTTP_OK,
            [],
            [
                "groups" =>
                [
                    "api_posts_related",
                ]
            ]);
    }

}
