<?php
// src/Service/UserService.php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\Role;
use App\ValueObject\Name;
use App\ValueObject\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use App\Entity\Borrow;
use App\Service\BookService;

class UserService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private $validator;
    private $bookService;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, UserRepository $userRepository, BookService $bookService)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->validator = $validator;
        $this->bookService = $bookService;
    }

    // Mthod for creating user object
    public function createUser(string $name, string $email, string $password, $role): User
    {

        $user = new User(new Name($name), new Email($email), $password, $role);

        return $user;

    }

    // Method for save user info
    public function saveUser(User $user): void
    {
        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new ValidatorException(implode(', ', $errors));
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    // Update user
    public function updateUser($id, $data): User
    {
        // check if user exist or not
        $user = $this->getUserById($id);
        if (!$user) {
            throw new \Exception("User not found");
        }
        $user->setName(new Name($data['name']));
        $user->setEmail(new Email($data['email']));
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setRole(Role::from($data['role']));

        return $user;
    }

    // getUser by id method
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findOneBy(['id' => $id, 'deletionStatus' => 'active']);
    }

    // Method for list all user details
    public function listUsers(): array
    {
        return $this->userRepository->findAll();
    }

    // Business logic for borrow book by userid and bookid
    public function borrowBook($data): bool
    {
        $user = $this->getUserById($data['userid']);
        $book = $this->bookService->getBookById($data['bookid']);
        $borrow = new Borrow();
        $borrow->setUserid($user);
        $borrow->setBookid($book);
        $borrow->setBorrowDate((new \DateTimeImmutable('now')));

        $this->entityManager->persist($borrow);
        $this->entityManager->flush();
        return true;
    }
    //method for return book
    public function returnBook(Borrow $borrow): Borrow
    {
        $borrow->setReturnDate((new \DateTimeImmutable('now')));

        $this->entityManager->persist($borrow);
        $this->entityManager->flush();

        return $borrow;
    }
    // Method to fetch username
    public function getUserName($id)
    {
        $userName = $this->userRepository->find($id);
        return $userName->getName();
    }

}
?>