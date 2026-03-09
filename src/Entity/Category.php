<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;


#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api_posts_read', 'api_posts_category', 'api_posts_home'])]
    private ?int $id = null;

    #[ORM\Column(length: 70, nullable: true)]
    #[Groups(['api_posts_read', 'api_posts_category', 'api_posts_all', 'api_posts_desc', 'api_posts_subcategory', 'api_posts_keyword' ])]
    private ?string $name = null;

    #[ORM\Column(length: 70, nullable: true)]
    #[Groups(['api_posts_read', 'api_posts_category', 'api_posts_all', 'api_posts_subcategory' ])]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Posts::class)]
    private Collection $posts;


    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, Posts>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Posts $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setCategory($this);
        }

        return $this;
    }

    public function removeArticle(Posts $post): self
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getCategory() === $this) {
                $post->setCategory(null);
            }
        }

        return $this;
    }

}
