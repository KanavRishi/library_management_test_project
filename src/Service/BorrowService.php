<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BorrowRepository;
use App\Service\UserService;
use App\Entity\Borrow;
use App\Service\BookService;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BorrowService
{
    private $entityManager;
    private $validator;
    private BorrowRepository $borrowRepository;
    private UserService $userService;
    private BookService $bookService;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, BorrowRepository $borrowRepository, UserService $userService, BookService $bookService)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->borrowRepository = $borrowRepository;
        $this->bookService = $bookService;
        $this->userService = $userService;
    }

    // Method for fetching borrow history
    public function getBorrowHistory(): array
    {
        return $this->borrowRepository->findAll();
    }

    // Method to get Borrow record by Id
    public function getBorrowBookById(int $id): Borrow
    {
        return $this->borrowRepository->find($id);
    }
}