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
use Symfony\Component\Validator\Exception\ValidatorException;

class BookController extends AbstractController
{
    private BookService $bookService;

    // Intialize bookService and logger
    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
    }

    // Add Book 
    #[Route('/book', methods: ['POST'], name: 'add_book')]
    public function addBook(Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validate status
            $validStatuses = array_map(fn($status) => $status->value, Status::cases());
            if (!in_array($data['status'], $validStatuses, true)) {
                return new JsonResponse([
                    'status' => $data['status'],
                    "message" => "Invalid Status Valid"
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            if (
                !isset($data['title']) || !isset($data['author']) || !isset($data['isbn'])
                || !isset($data['status']) || !isset($data['publisheddate'])
            ) {
                return new JsonResponse([
                    'status' => $data['status'],
                    "message" => "Please input all fields"
                ], Response::HTTP_BAD_REQUEST);
            }

            // Creating and Saving the book and validations
            $publishedDate = new \DateTime($data['publisheddate']);
            $status = Status::from($data['status']);

            // Create and save the book using the service
            $book = $this->bookService->createBook(
                $data['author'],
                $data['title'],
                $data['isbn'],
                $data['status'],
                $publishedDate
            );
            // Save Book Logic
            $this->bookService->saveBook($book);
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Book added successfully!',
            ], JsonResponse::HTTP_CREATED);
        } catch (UniqueConstraintViolationException $e) {
            // Handle unique constraint violations
            return new JsonResponse([
                'status' => 'error',
                'message' => 'A book with this ISBN already exists.',
            ], JsonResponse::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $e) {
            // Validation for other unexpected errors
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            // Validation for other unexpected errors
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
                return new JsonResponse([
                    'status' => 'error',
                    "message" => "Invalid ID Provided"
                ], Response::HTTP_BAD_REQUEST);
            }

            // Get Book by ID
            $checkBook = $this->bookService->getBookById($id);
            if (empty($checkBook)) {
                return new JsonResponse([
                    "status" => "error",
                    "message" => "Book does not exist"
                ], Response::HTTP_BAD_REQUEST);
            }

            // Decode request data
            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                return new JsonResponse([
                    "status" => "error",
                    "message" => "Invalid Request Data"
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate required fields
            $requiredFields = ['title', 'author', 'isbn', 'status', 'publisheddate'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return new JsonResponse([
                        'status' => 'error',
                        "message" => "Missing field: $field."
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Validate Status
            $validStatuses = array_map(fn($status) => $status->value, Status::cases());
            if (!in_array($data['status'], $validStatuses, true)) {
                return new JsonResponse([
                    'status' => 'error',
                    "message" => "Invalid Status Value"
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate Published Date
            $publishedDate = new \DateTime($data['publisheddate']);

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
            ], JsonResponse::HTTP_BAD_REQUEST);
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
    public function getBookById($id): JsonResponse
    {
        // id should be numeric and positive integer
        try {
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                return new JsonResponse([
                    'status' => 'error',
                    "message" => "Invalid ID Provided"
                ], Response::HTTP_BAD_REQUEST);
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
        try {
            // id should be numeric and positive integer
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid Id provided'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // get book by id 
            $book = $this->bookService->getBookById($id);
            if (empty($book)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Book does not exist'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
            $book->setStatus(Status::DELETED);

            // Update status to deleted
            $this->bookService->saveBook($book);
            return new JsonResponse(["message" => "Book Deleted Successfully:"], JsonResponse::HTTP_OK);
        } catch (ValidatorException $e) {
            return new JsonResponse([
                "message" => "Unexpected Error:" . $e->getMessage()
                ],JsonResponse::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
