<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Enum\Status;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use App\Service\BookService;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

class BookController extends AbstractController
{
    private BookService $bookService;
    private LoggerInterface $logger;

    // Intialize bookService and logger
    public function __construct(BookService $bookService, LoggerInterface $logger)
    {
        $this->bookService = $bookService;
        $this->logger = $logger;
    }

    // Add Book 
    #[Route('/book', methods: ['POST'], name: 'add_book')]
    public function addBook(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        // check all variables
        try {
            $status = Status::from($data['status']);
        } catch (\ValueError $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid status value'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        if (!isset($data['title']) || !isset($data['author']) || !isset($data['isbn']) || !isset($data['status']) || !isset($data['publisheddate'])) {
            return new JsonResponse(["message" => "Please input all values"], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Proceed with creating and saving the book and validations
        try {
            $publishedDate = new \DateTime($data['publisheddate']);

            // Create and save the book using the service
            $book = $this->bookService->createBook($data['author'], $data['title'], $data['isbn'], $data['status'], $publishedDate);
            // dd($book);
            $this->bookService->saveBook($book);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Book added successfully!',
            ], JsonResponse::HTTP_CREATED);

        } catch (UniqueConstraintViolationException $e) {
            // Handle unique constraint violations
            $this->logger->error('Duplicate entry error: ' . $e->getMessage());

            return new JsonResponse([
                'status' => 'error',
                'message' => 'A book with this ISBN already exists.',
            ], JsonResponse::HTTP_CONFLICT);

        } catch (\Exception $e) {
            // Validation for other unexpected errors
            $this->logger->error('Unexpected error: ' . $e->getMessage());
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Update Book
    #[Route('/book/{id}', methods: ['PUT'], name: 'update_book')]
    public function updateBook(Request $request, $id, ValidatorInterface $validator): JsonResponse
    {
        try {
            // Validate ID
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                throw new \InvalidArgumentException('Invalid ID provided.');
            }

            // Get Book by ID
            $checkBook = $this->bookService->getBookById($id);
            if (empty($checkBook)) {
                throw new \Exception('Book does not exist.');
            }

            // Decode request data
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                throw new \InvalidArgumentException('Invalid request data.');
            }

            // Validate required fields
            $requiredFields = ['title', 'author', 'isbn', 'status', 'publisheddate'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \InvalidArgumentException("Missing field: $field.");
                }
            }

            // Validate Status
            try {
                $status = Status::from($data['status']);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Invalid status value.');
            }

            // Validate Published Date
            try {
                $publishedDate = new \DateTimeImmutable($data['publisheddate']);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Invalid date format. Expected format: Y-m-d.');
            }

            // Create or update the Book entity
            $book = $this->bookService->updateBook($id, $data);
            $this->bookService->saveBook($book);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Book updated successfully.',
            ], JsonResponse::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // All Books List
    #[Route('/book', methods: ["GET"], name: 'list_book')]
    public function listBook(): JsonResponse
    {
        // Fetch Book List from database
        $books = $this->bookService->listBooks();
        if ($books) {
            $responseData = array_map(function ($books) {
                return [
                    'id' => $books->getId(),
                    'title' => $books->getTitle(),
                    'author' => $books->getAuthor(),
                    'isbn' => $books->getIsbn(),
                    'PublishedDate' => $books->getPublishedDate()->format('Y-m-d'),
                    'status' => $books->getStatus()->value
                ];
            }, $books);
            return $this->json(
                [
                    'status' => 'success',
                    'data' => $responseData
                ]
            );
        }
        return $this->json(['status' => 'Book not Found'], Response::HTTP_OK);
    }

    // Get Book by Id
    #[Route('/book/{id}/', methods: ['GET'])]
    public function getBookById(int $id): JsonResponse
    {
        // id should be numeric and positive integer
        try {
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                throw new \InvalidArgumentException('Invalid ID provided.');
            }

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);

        }
        // check whether book exist or not
        $book = $this->bookService->getBookById($id);

        if (!$book) {
            return $this->json(['status' => 'Book not Found'], Response::HTTP_NOT_FOUND);
        }

        $responseData = [
            'status' => 'success',
            'data' => [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'author' => $book->getAuthor(),
                'isbn' => $book->getIsbn(),
                'PublishedDate' => $book->getPublishedDate()->format('Y-m-d'),
                'status' => $book->getStatus()->value
            ]
        ];
        return $this->json($responseData);
    }

    // Delete Book (Soft Delete: updation of status to deleted)
    #[Route('/book/delete/{id}', methods: ['DELETE'])]
    public function deleteBook($id): JsonResponse
    {
        // id should be numeric and positive integer
        try {
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                throw new \InvalidArgumentException('Invalid ID provided.');
            }
            // get book by id 

            $book = $this->bookService->getBookById($id);
            if (empty($book)) {
                throw new \Exception("Book does not exist");
            }

            $book->setStatus(Status::DELETED);

            // Update status to deleted

            $this->bookService->saveBook($book);
            return new JsonResponse(["message" => "Book Deleted Successfully:"], JsonResponse::HTTP_OK);
        } catch (ValidatorException $e) {
            return new JsonResponse(["message" => "Unexpected Error:" . $e->getMessage()], JsonResponse::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);

        }
    }
}
