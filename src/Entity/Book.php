<?php

namespace App\Entity;

use App\Enum\Status;
use App\Repository\BookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\ValueObject\Title;
use App\ValueObject\Isbn;
use App\ValueObject\Author;
use Symfony\Component\Validator\Constraints as Assert;
use App\Traits\TimeStampableTrait;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Embedded(class: Title::class)]
    private Title $title;

    #[ORM\Embedded(class: Author::class)]
    private Author $author;

    #[ORM\Embedded(class: Isbn::class)]
    #[Assert\NotNull]
    #[Assert\Length(
        min: 10,
        max: 13,
        exactMessage: 'ISBN must be either 10 or 13 characters long.',
    )]
    private Isbn $isbn;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    private ?\DateTimeInterface $publishedDate = null;

    #[ORM\Column(enumType: Status::class)]
    #[Assert\NotNull]
    private ?Status $status = null;

    public function __construct(Author $author, Title $title, Isbn $isbn, \DateTimeInterface $publishedDate)
    {
        $this->author = $author;
        $this->title = $title;
        $this->isbn = $isbn;
        $this->publishedDate = $publishedDate;
        $this->status = Status::AVAILABLE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(Title $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(Author $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getIsbn(): string
    {
        return $this->isbn;
    }

    public function setIsbn(Isbn $isbn): self
    {
        $this->isbn = $isbn;
        return $this;
    }

    public function getPublishedDate(): ?\DateTimeInterface
    {
        return $this->publishedDate;
    }

    public function setPublishedDate(\DateTimeInterface $publishedDate): static
    {
        $this->publishedDate = $publishedDate;
        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): static
    {
        $this->status = $status;
        return $this;
    }

    use TimeStampableTrait;

}
