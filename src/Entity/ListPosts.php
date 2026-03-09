<?php

namespace App\Entity;

use App\Repository\ListPostsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ListPostsRepository::class)]
#[ApiResource]
class ListPosts
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api_posts_read'])]
    private ?int $id = null;

    #[ORM\Column(length: 170, nullable: true)]
    #[Groups(['api_posts_read', 'api_posts_home'])]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'ListPosts')]
    private ?Posts $posts = null;

    #[ORM\Column(length: 5000, nullable: true)]
    #[Groups(['api_posts_read', 'api_posts_home'])]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['api_posts_read'])]
    private ?string $link = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['api_posts_read'])]
    private ?string $linkSubtitle = null;

    #[ORM\ManyToOne(targetEntity: Posts::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $linkPostSelect;

    public function __toString(): string
    {
        return $this->title ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPosts(): ?Posts
    {
        return $this->posts;
    }

    public function setPosts(?Posts $posts): self
    {
        $this->posts = $posts;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getLinkSubtitle(): ?string
    {
        return $this->linkSubtitle;
    }

    public function setLinkSubtitle(?string $linkSubtitle): self
    {
        $this->linkSubtitle = $linkSubtitle;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getLinkPostSelect(): ?Posts
    {
        return $this->linkPostSelect;
    }

    public function setLinkPostSelect(?Posts $linkPostSelect): self
    {
        $this->linkPostSelect = $linkPostSelect;

        return $this;
    }
}
