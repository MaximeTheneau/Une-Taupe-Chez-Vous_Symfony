<?php

namespace App\Entity;

use App\Repository\CommentsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CommentsRepository::class)]
class Comments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api_posts_read'])]
    private ?int $id = null;

    #[ORM\Column(length: 70)]
    #[Groups(['api_posts_read'])]
    private ?string $User = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 2000)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['api_posts_read'])]
    private ?string $comment = null;

    #[ORM\Column]
    private ?bool $accepted = false;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    private ?Posts $posts = null;

    #[ORM\Column]
    #[Groups(['api_posts_read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'replies', targetEntity: 'Comments')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true)]
    private $parent;

    public function __toString(): string
    {
        return ($this->User ?? '') . ' — ' . substr($this->comment ?? '', 0, 50);
    }

    #[ORM\OneToMany(targetEntity: Comments::class , mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['api_posts_read'])]
    private $replies;

    #[ORM\Column(nullable: true)]
    private ?bool $replyToComment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?string
    {
        return $this->User;
    }

    public function setUser(string $User): static
    {
        $this->User = $User;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getComment(): ?string
    {

        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function isAccepted(): ?bool
    {

        return $this->accepted;
    }

    public function setAccepted(bool $accepted): static
    {
        $this->accepted = $accepted;

        return $this;
    }

    public function getPosts(): ?Posts
    {
        return $this->posts;
    }

    public function setPosts(?Posts $posts): static
    {
        $this->posts = $posts;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return ArrayCollection|Comment[]
     */
    public function getReplies()
    {
        return $this->replies->toArray();
    }

    public function setReplies($replies): static
    {
        $this->replies = $replies;

        return $this;
    }

    public function getParent(): ?Comments
    {
        return $this->parent;
    }

    public function setParent(?Comments $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function removeComments(Comments $comment): static
    {
        // Supprimer le commentaire de la collection principale
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPosts() === $this) {
                $comment->setPosts(null);
            }
        }

        // Supprimer le commentaire de la collection de réponses
        if ($this->replies->contains($comment)) {
            $this->replies->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getParent() === $this) {
                $comment->setParent(null);
            }
        }

        return $this;
    }

    public function isReplyToComment(): ?bool
    {
        return $this->replyToComment;
    }

    public function setReplyToComment(?bool $replyToComment): static
    {
        $this->replyToComment = $replyToComment;

        return $this;
    }

}
