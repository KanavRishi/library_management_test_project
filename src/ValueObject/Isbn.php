<?php

namespace App\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Embeddable]
class Isbn
{
    #[ORM\Column(type: 'string', unique: true, length: 15)]
    #[Assert\Regex(
        pattern: '/^\d+$/',
        message: "ISBN must contain only numbers."
    )]
    private string $value;

    public function __construct(string $value)
    {
        if (strlen($value) !== 10 && strlen($value) !== 13) {
            throw new \InvalidArgumentException('Invalid ISBN. It must be either 10 or 13 characters long.');
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
