<?php

namespace App\Entity;

use App\Enum\Role;
use App\Enum\DeletionStatus;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use App\ValueObject\Name;
use App\ValueObject\Email;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull]
    #[ORM\Embedded(class: Name::class)]
    private ?Name $name;
    
    #[Assert\NotBlank(message: 'Email address cannot be blank.')]
    #[Assert\Email(
        message: 'The email "{{ value }}" is not a valid email address.',
        mode: 'strict' // This can be 'loose', 'strict', or 'html5'
    )]
    #[ORM\Embedded(class: Email::class)]
    private ?Email $email;

    #[ORM\Column(enumType: Role::class)]
    private ?Role $Role = null;

    #[ORM\Column(length: 100)]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(type: "string", enumType: DeletionStatus::class)]
    private DeletionStatus $deletionStatus;
    
    public function __construct(Name $name,Email $email,string $password)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->Role = ROLE::MEMBER;
        $this->deletionStatus = DeletionStatus::ACTIVE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?Name
    {
        return $this->name;
    }

    public function setName(Name $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function setEmail(Email $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRole(): ?Role
    {
        return $this->Role;
    }

    public function setRole(Role $Role): static
    {
        $this->Role = $Role;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatedTimestamps()
    {
        $this->setUpdatedAt(new \DateTimeImmutable('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTimeImmutable('now'));
        }
    }
    public function getDeletionStatus(): DeletionStatus
    {
        return $this->deletionStatus;
    }

    public function setDeletionStatus(DeletionStatus $status): self
    {
        $this->deletionStatus = $status;

        return $this;
    }
}
