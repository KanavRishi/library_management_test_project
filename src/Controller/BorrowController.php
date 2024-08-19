<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\BorrowRepository;
use App\Service\BorrowService;
use Symfony\Component\HttpFoundation\JsonResponse;

class BorrowController extends AbstractController
{
    private BorrowService $borrowService;
    

    public function __construct(BorrowService $borrowService)
    {
        $this->borrowService=$borrowService;
    }
    #[Route('/borrow/history', methods: ['GET'], name: 'borrow_history')]
    public function getBorrowHistory(): JsonResponse
    {
        $borrowHistory = $this->borrowService->getBorrowHistory();

        $responseData = array_map(function($borrow) {
            return [
                'id' => $borrow->getId(),
                'userId' => $borrow->getUserId()->getId(),
                'bookId' => $borrow->getBookId()->getId(),
                'borrowDate' => $borrow->getBorrowDate()->format('Y-m-d'),
                'returnDate' => $borrow->getReturnDate()->format('Y-m-d'),
            ];
        }, $borrowHistory);

        return $this->json([
            'status' => 'success',
            'data' => $responseData
        ],JsonResponse::HTTP_OK);
    }
}
