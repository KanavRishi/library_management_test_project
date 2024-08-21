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
        // Published Date 
        $publishedDate = \DateTime::createFromFormat('Y-m-d', $data['publisheddate']);
        // published date validation
        if (!$publishedDate) {
            throw new \InvalidArgumentException('Invalid date format. Expected format: Y-m-d');
        }
        // check if the variables are set or not
        if (isset($data['title'], $data['author'], $data['isbn'], $publishedDate)) {
            // Status Validation
            try {
                $status = Status::from($data['status']);
            } catch (\ValueError $e) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid status value'
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
            // Create Book and Save Book
            try {
                $book = $this->bookService->createBook($data['author'], $data['title'], $data['isbn'], $data['status'], $publishedDate);
                $this->bookService->saveBook($book);

                return new JsonResponse([
                    'status' => 'success',
                    'message' => 'Book added successfully!'
                ], JsonResponse::HTTP_CREATED);
            } catch (ValidatorException $e) {
                // Exception for Validation
                $this->logger->error('Validation error: ' . $e->getMessage());

                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Validation failed.',
                    'errors' => json_decode($e->getMessage(), true)
                ], JsonResponse::HTTP_BAD_REQUEST);
            } catch (UniqueConstraintViolationException $e) {
                // Exception for Unique data
                $this->logger->error('Duplicate entry error: ' . $e->getMessage());

                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'A book with this ISBN already exists.'
                ], JsonResponse::HTTP_CONFLICT);
            } catch (\InvalidArgumentException | \ValueError $e) {
                $this->logger->error('Input error: ' . $e->getMessage());

                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid input: ' . $e->getMessage()
                ], JsonResponse::HTTP_BAD_REQUEST);
            } catch (\Exception $e) {
                $this->logger->error('Unexpected error: ' . $e->getMessage());

                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'An unexpected error occurred: ' . $e->getMessage()
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Please provide all required data.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    // Update Book
    #[Route('/book/{id}', methods: ['PUT'], name: 'update_book')]
    public function updateBook(Request $request, $id, ValidatorInterface $validator): JsonResponse
    {
        // Check wheter id is Integer and Positive value
        try {
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                throw new \InvalidArgumentException('Invalid ID provided.');
            }

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
        // Get Book by id 
        try {
            $checkBook = $this->bookService->getBookById($id);
            if (empty($checkBook)) {
                throw new \Exception("Book does not exist");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }
        // New content for updation
        $data = json_decode($request->getContent(), true);

        // check Publish date validation 
        try {
            $publishedDate = \DateTime::createFromFormat('Y-m-d', $data['publisheddate']);
            if (!$publishedDate) {
                throw new \InvalidArgumentException('Invalid date format. Expected format: Y-m-d');
            }
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(["message" => "Unexpected Error:" . $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Status Validation
        try {
            $status = Status::from($data['status']);
        } catch (\ValueError $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid status value'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // create object for update book 
        try {
            $book = $this->bookService->updateBook($id, $data);
        } catch (\Exception $e) {
            return new JsonResponse(["message" => "Unexpected Error:" . $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        // persisit the book object into Book table and check its validation
        try {
            $this->bookService->saveBook($book);
            return new JsonResponse(["message" => "Book Updated Successfully:"], JsonResponse::HTTP_OK);
        } catch (ValidatorException $e) {
            return new JsonResponse(["message" => "Unexpected Error:" . $e->getMessage()], JsonResponse::HTTP_CONFLICT);
        } catch (UniqueConstraintViolationException $e) {
            $this->logger->error('Duplicate entry error: ' . $e->getMessage());

            return new JsonResponse([
                'status' => 'error',
                'message' => 'A book with this ISBN already exists.'
            ], JsonResponse::HTTP_CONFLICT);
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

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
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
    public function deleteBook(int $id): JsonResponse
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

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // get book by id 
        try {
            $book = $this->bookService->getBookById($id);
            if (empty($book)) {
                throw new \Exception("Book does not exist");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }
        $book->setStatus(Status::DELETED);

        // Update status to deleted
        try {
            $this->bookService->saveBook($book);
            return new JsonResponse(["message" => "Book Deleted Successfully:"], JsonResponse::HTTP_OK);
        } catch (ValidatorException $e) {
            return new JsonResponse(["message" => "Unexpected Error:" . $e->getMessage()], JsonResponse::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}
