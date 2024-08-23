<?php

namespace App\Entity;

use App\Enum\Role;
use App\Enum\DeletionStatus;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use App\ValueObject\Name;
use App\ValueObject\Email;
use Symfony\Component\Validator\Constraints as Assert;
use App\Traits\TimeStampableTrait;

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

    #[ORM\Column(type: "string", enumType: DeletionStatus::class)]
    private DeletionStatus $deletionStatus;

    public function __construct(Name $name, Email $email, string $password)
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(Name $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
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

    use TimeStampableTrait;

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
