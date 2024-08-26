<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\BorrowRepository;
use App\Service\BorrowService;
use App\Service\UserService;
use App\Service\BookService;
use Symfony\Component\HttpFoundation\JsonResponse;

class BorrowController extends AbstractController
{
    private BorrowService $borrowService;
    private UserService $userService;
    private BookService $bookService;


    public function __construct(BorrowService $borrowService, UserService $userService, BookService $bookService)
    {
        $this->borrowService = $borrowService;
        $this->bookService = $bookService;
        $this->userService = $userService;
    }
    // borrow history of books
    #[Route('/borrow/history', methods: ['GET'], name: 'borrow_history')]
    public function getBorrowHistory(): JsonResponse
    {
        //get borrow history from getBorrowHistory method
        $borrowHistory = $this->borrowService->getBorrowHistory();

        $responseData = array_map(function ($borrow) {
            return [
                'id' => $borrow->getId(),
                'UserName' => $this->userService->getUserName($borrow->getUserId()->getId()),
                'Book Title' => $this->bookService->getBookTitle($borrow->getBookId()->getId()),
                'borrowDate' => $borrow->getBorrowDate()->format('Y-m-d'),
                'returnDate' => $borrow->getReturnDate() ? $borrow->getReturnDate()->format('Y-m-d') : null,
            ];
        }, $borrowHistory);

        return $this->json([
            'status' => 'success',
            'data' => $responseData
        ], JsonResponse::HTTP_OK);
    }
}
