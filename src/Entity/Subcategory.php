<?php

namespace App\Entity;

use App\Repository\SubcategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Category;

#[ORM\Entity(repositoryClass: SubcategoryRepository::class)]
class Subcategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 70, nullable: true)]
    #[Groups(['api_posts_category', 'api_posts_browse', 'api_posts_desc', 'api_posts_subcategory', 'api_posts_read', 'api_posts__allSubcategory', 'api_posts_all', 'api_posts_keyword' ])]
    private ?string $name = null;

    #[ORM\Column(length: 70, nullable: true)]
    #[Groups(['api_posts_category', 'api_posts_browse', 'api_posts_desc', 'api_posts_subcategory', 'api_posts_read', 'api_posts__allSubcategory', 'api_posts_all', 'api_posts_keyword' ])]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'subcategory', targetEntity: Posts::class)]
    private Collection $posts;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Category $category = null;

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function __construct()
    {
        $this->posts = new ArrayCollection();
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Posts $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setSubcategory($this);
        }

        return $this;
    }

    public function removePost(Posts $post): self
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getSubcategory() === $this) {
                $post->setSubcategory(null);
            }
        }

        return $this;
    }


}
