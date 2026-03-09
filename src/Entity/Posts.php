<?php

namespace App\Entity;

use App\Repository\PostsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;


#[ORM\Entity(repositoryClass: PostsRepository::class)]
#[Groups(['api_posts_keyword' ])]
class Posts
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api_posts_read', 'api_posts_desc', 'api_posts_category', 'api_posts_keyword', 'api_posts_home' ])]
    private ?int $id = null;

    #[ORM\Column(length: 70)]
    #[Groups(['api_posts_read', 'api_posts_home'])]
    private ?string $heading = null;

    #[ORM\Column(length: 70, unique: true, type: Types::STRING)]
    #[Groups(['api_posts_home', 'api_posts_read', 'api_posts_desc', 'api_posts_category', 'api_posts_subcategory', 'api_posts_articles_desc', 'api_posts_all', 'api_posts_keyword' ])]
    private ?string $title = null;

    #[ORM\Column(length: 1000)]
    #[Groups(['api_posts_read', 'api_posts_home', 'api_posts_category'])]
    private ?string $metaDescription = null;

    #[ORM\Column(length: 70, unique: true, type: Types::STRING)]
    #[Groups(['api_posts_home', 'api_posts_read', 'api_posts_desc', 'api_posts_category', 'api_posts_subcategory', 'api_posts_all', 'api_posts_keyword', 'api_posts_sitemap' ])]
    private ?string $slug = null;

    #[ORM\Column(length: 5000, nullable: true, type: Types::STRING)]
    #[Groups(['api_posts_read', 'api_posts_home'])]
    private ?string $contents = null;

    #[ORM\Column]
    #[Groups(['api_posts_read', 'api_posts_category', 'api_posts_sitemap' ])]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api_posts_read', 'api_posts_category', 'api_posts_sitemap'])]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(length: 255)]
    #[Groups(['api_posts_read' ])]
    private ?string $formattedDate = null;

    #[ORM\OneToMany(mappedBy: 'posts', targetEntity: ListPosts::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['api_posts_read', 'api_posts_home' ])]
    private Collection $listPosts;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $links = null;

    #[ORM\OneToMany(mappedBy: 'posts', targetEntity: ParagraphPosts::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['api_posts_read', 'api_posts_sitemap', 'api_posts_home' ])]
    private Collection $paragraphPosts;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $textLinks = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[Groups(['api_posts_read', 'api_posts_category', 'api_posts_all', 'api_posts_desc', 'api_posts_subcategory', 'api_posts_category','api_posts_keyword' ])]
    private ?Category $category = null;

    #[ORM\Column(length: 125, nullable: true)]
    #[Groups(['api_posts_category', 'api_posts_home', 'api_posts_read',  'api_posts_desc', 'api_posts_keyword', 'api_posts_all', 'api_posts_category', 'api_posts_subcategory'])]
    private ?string $altImg = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['api_posts_read', 'api_posts_all',  'api_posts_desc', 'api_posts_subcategory', 'api_posts_category', 'api_posts_keyword', 'api_posts_sitemap', 'api_posts_home' ])]
    private ?string $imgPost = null;

    private ?File $imageFile = null;
    private bool $deleteImage = false;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[Groups(['api_posts_all', 'api_posts_category', 'api_posts_desc', 'api_posts_subcategory', 'api_posts_read', 'api_posts_keyword'])]
    private ?Subcategory $subcategory = null;

    #[ORM\OneToMany(mappedBy: 'posts', targetEntity: Comments::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['api_posts_read'])]
    private Collection $comments;

    #[ORM\ManyToMany(targetEntity: Keyword::class, mappedBy: 'posts')]
    #[Groups(['api_posts_read'])]
    private Collection $keywords;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['api_posts_all', 'api_posts_category', 'api_posts_desc', 'api_posts_subcategory', 'api_posts_read', 'api_posts_keyword', 'api_posts_sitemap', 'api_posts_home'  ])]
    private ?string $url = null;

    #[ORM\Column(length: 5000)]
    #[Groups(['api_posts_read', 'api_posts_home'])]
    private ?string $contentsHTML = null;

    #[ORM\Column(nullable: true)]
    private ?bool $draft = null;

    #[ORM\Column( nullable: true)]
    #[Groups(['api_posts_read', 'api_posts_home'])]
    private ?int $imgWidth = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['api_posts_read', 'api_posts_home'])]
    private ?int $imgHeight = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['api_posts_read', 'api_posts_home'])]
    private ?string $srcset = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isHomeImage = null;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'posts', cascade: ['persist'],)]
    #[Groups(['api_posts_related'])]
    private Collection $relatedPosts;

    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'relatedPosts')]
    private Collection $posts;

    public function __toString(): string
    {
        return $this->title ?? '';
    }

    public function __construct()
    {
        $this->listPosts = new ArrayCollection();
        $this->paragraphPosts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->keywords = new ArrayCollection();
        $this->relatedPosts = new ArrayCollection();
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContents(): ?string
    {
        return $this->contents;
    }

    public function setContents(string $contents): self
    {
        $this->contents = $contents;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }


    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, ListPosts>
     */
    public function getListPosts(): Collection
    {
        return $this->listPosts;
    }

    public function addListPost(ListPosts $listPost): self
    {
        if (!$this->listPosts->contains($listPost)) {
            $this->listPosts->add($listPost);
            $listPost->setPosts($this);
        }

        return $this;
    }

    public function removeListPost(ListPosts $listPost): self
    {
        if ($this->listPosts->removeElement($listPost)) {
            // set the owning side to null (unless already changed)
            if ($listPost->getPosts() === $this) {
                $listPost->setPosts(null);
            }
        }

        return $this;
    }

    public function getLinks(): ?string
    {
        return $this->links;
    }

    public function setLinks(?string $links): self
    {
        $this->links = $links;

        return $this;
    }

    /**
     * @return Collection<int, ParagraphPosts>
     */
    public function getParagraphPosts(): Collection
    {
        return $this->paragraphPosts;
    }

    public function addParagraphPost(ParagraphPosts $paragraphPost): self
    {
        if (!$this->paragraphPosts->contains($paragraphPost)) {
            $this->paragraphPosts->add($paragraphPost);
            $paragraphPost->setPosts($this);
        }

        return $this;
    }

    public function removeParagraphPost(ParagraphPosts $paragraphPost): self
    {
        if ($this->paragraphPosts->removeElement($paragraphPost)) {
            // set the owning side to null (unless already changed)
            if ($paragraphPost->getPosts() === $this) {
                $paragraphPost->setPosts(null);
            }
        }

        return $this;
    }

    public function getTextLinks(): ?string
    {
        return $this->textLinks;
    }

    public function setTextLinks(?string $textLinks): self
    {
        $this->textLinks = $textLinks;

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

    public function getAltImg(): ?string
    {
        return $this->altImg;
    }

    public function setAltImg(?string $altImg): self
    {
        $this->altImg = $altImg;

        return $this;
    }

    public function getImgPost(): ?string
    {
        return $this->imgPost;
    }

    public function setImgPost(?string $imgPost): self
    {
        $this->imgPost = $imgPost;

        return $this;
    }


    public function getImageFile(): ?File { return $this->imageFile; }
    public function setImageFile(?File $file): static { $this->imageFile = $file; return $this; }

    public function isDeleteImage(): bool { return $this->deleteImage; }
    public function setDeleteImage(bool $v): static { $this->deleteImage = $v; return $this; }

    // Aliases pour ImageOptimizer::setPicture() qui utilise getImg()/setImg()
    public function getImg(): ?string { return $this->imgPost; }
    public function setImg(?string $url): static { $this->imgPost = $url; return $this; }


    public function getSubcategory(): ?Subcategory
    {
        return $this->subcategory;
    }

    public function setSubcategory(?Subcategory $subcategory): self
    {
        $this->subcategory = $subcategory;

        return $this;
    }

    /**
     * @return Collection<int, Comments>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comments $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPosts($this);
        }

        return $this;
    }

    public function removeComment(Comments $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPosts() === $this) {
                $comment->setPosts(null);
            }
        }

        return $this;
    }

    public function setComments(Collection $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * @return Collection<int, Keyword>
     */
    public function getKeywords(): Collection
    {
        return $this->keywords;
    }

    public function addKeyword(Keyword $keyword): static
    {
        if (!$this->keywords->contains($keyword)) {
            $this->keywords->add($keyword);
            $keyword->addPost($this);
        }

        return $this;
    }

    public function removeKeyword(Keyword $keyword): static
    {
        if ($this->keywords->removeElement($keyword)) {
            $keyword->removePost($this);
        }

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getFormattedDate(): ?string
    {
        return $this->formattedDate;
    }

    public function setFormattedDate(string $formattedDate): static
    {
        $this->formattedDate = $formattedDate;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getContentsHTML(): ?string
    {
        return $this->contentsHTML;
    }

    public function setContentsHTML(string $contentsHTML): static
    {
        $this->contentsHTML = $contentsHTML;

        return $this;
    }

    public function isDraft(): ?bool
    {
        return $this->draft;
    }

    public function setDraft(?bool $draft): static
    {
        $this->draft = $draft;

        return $this;
    }

    public function getHeading(): ?string
    {
        return $this->heading;
    }

    public function setHeading(string $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function getImgWidth(): ?int
    {
        return $this->imgWidth;
    }

    public function setImgWidth(?string $imgWidth): static
    {
        $this->imgWidth = $imgWidth;

        return $this;
    }

    public function getImgHeight(): ?int
    {
        return $this->imgHeight;
    }

    public function setImgHeight(?int $imgHeight): static
    {
        $this->imgHeight = $imgHeight;

        return $this;
    }

    public function getSrcset(): ?string
    {
        return $this->srcset;
    }

    public function setSrcset(?string $srcset): static
    {
        $this->srcset = $srcset;

        return $this;
    }

    public function isIsHomeImage(): ?bool
    {
        return $this->isHomeImage;
    }

    public function setIsHomeImage(?bool $isHomeImage): static
    {
        $this->isHomeImage = $isHomeImage;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getRelatedPosts(): Collection
    {
        return $this->relatedPosts;
    }

    public function addRelatedPost(self $relatedPost): static
    {
        if (!$this->relatedPosts->contains($relatedPost)) {
            $this->relatedPosts->add($relatedPost);
        }

        return $this;
    }

    public function removeRelatedPost(self $relatedPost): static
    {
        $this->relatedPosts->removeElement($relatedPost);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(self $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->addRelatedPost($this);
        }

        return $this;
    }

    public function removePost(self $post): static
    {
        if ($this->posts->removeElement($post)) {
            $post->removeRelatedPost($this);
        }

        return $this;
    }
}
