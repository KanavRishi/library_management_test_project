<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Enum\Status;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\BorrowRepository;
use App\Entity\Borrow;
use App\ValueObject\Isbn;
use App\Entity\Book;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use App\Service\UserService;
use App\Service\BookService;
use App\Service\BorrowService;
use Psr\Log\LoggerInterface;
use App\Enum\DeletionStatus;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

class BookController extends AbstractController
{
    private BookService $bookService;
    private UserService $userService;
    private BorrowService $borrowService;
    private BorrowRepository $borrowRepository;
    private LoggerInterface $logger;

    public function __construct(BookService $bookService,UserService $userService,BorrowService $borrowService,BorrowRepository $borrowRepository, LoggerInterface $logger)
    {
        $this->bookService=$bookService;
        $this->userService=$userService;
        $this->borrowService=$borrowService;
        $this->borrowRepository=$borrowRepository;
        $this->logger = $logger;
    }

    #[Route('/book',methods:['POST'], name: 'add_book')]
    public function addBook(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $publishedDate = \DateTime::createFromFormat('Y-m-d', $data['publisheddate']);
    
    if (!$publishedDate) {
        throw new \InvalidArgumentException('Invalid date format. Expected format: Y-m-d');
    }
    
    if (isset($data['title'], $data['author'], $data['isbn'], $publishedDate)) {
        try {
            $status = Status::from($data['status']);
        } catch (\ValueError $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid status value'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        try {
            $isbnData = new Isbn($data['isbn']);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $createdAt = new \DateTimeImmutable('now');

        try {
            $book = $this->bookService->createBook($data['author'], $data['title'], $data['isbn'], $data['status'], $publishedDate, $createdAt);
            $this->bookService->saveBook($book);

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Book added successfully!'
            ], JsonResponse::HTTP_CREATED);

        } catch (ValidatorException $e) {
            $this->logger->error('Validation error: ' . $e->getMessage());

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => json_decode($e->getMessage(), true)
            ], JsonResponse::HTTP_BAD_REQUEST);

        } catch (UniqueConstraintViolationException $e) {
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
    public function updateBook(Request $request,int $id): JsonResponse
    {
        try {
            $checkBook = $this->bookService->getBookById($id);
            if(empty($checkBook))
            {            
                throw new \Exception("Book does not exist");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message"=>$e->getMessage()],JsonResponse::HTTP_NOT_FOUND);
        }
       $data = json_decode($request->getContent(),true);
        try {
        $publishedDate = \DateTime::createFromFormat('Y-m-d', $data['publisheddate']);
        if (!$publishedDate) {
            throw new \InvalidArgumentException('Invalid date format. Expected format: Y-m-d');
        }
        } catch (\InvalidArgumentException $e){
            return new JsonResponse(["message"=>"Unexpected Error:".$e->getMessage()],JsonResponse::HTTP_BAD_REQUEST);
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

        try {
            $book = $this->bookService->updateBook($id,$data);
        } catch (\Exception $e) {
            return new JsonResponse(["message"=>"Unexpected Error:".$e->getMessage()],JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $this->bookService->saveBook($book);
            return new JsonResponse(["message"=>"Book Updated Successfully:"],JsonResponse::HTTP_OK);
        } catch (ValidatorException $e) {
            return new JsonResponse(["message"=>"Unexpected Error:".$e->getMessage()],JsonResponse::HTTP_CONFLICT);
        } catch (\Exception $e) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], JsonResponse::HTTP_BAD_REQUEST);
        }  catch (UniqueConstraintViolationException $e) {
            $this->logger->error('Duplicate entry error: ' . $e->getMessage());
        
            return new JsonResponse([
                'status' => 'error',
                'message' => 'A book with this ISBN already exists.'
            ], JsonResponse::HTTP_CONFLICT); 

        }
            
       if (!isset($data['title'], $data['author'], $data['isbn'], $data['publisheddate'], $data['status'])) {
        return new JsonResponse([
            'status' => 'error',
            'message' => 'Please input all data'
        ], JsonResponse::HTTP_BAD_REQUEST);
    }
    }

    // All Books List
    #[Route('/book',methods:["GET"],name:'list_book')]
    public function listBook(): JsonResponse
    {
        $books = $this->bookService->listBooks();
        if($books)
        {
        $responseData = array_map(function($books){
            return[
                'id'=>$books->getId(),
                'title'=>$books->getTitle(),
                'author'=>$books->getAuthor(),
                'isbn'=>$books->getIsbn(),
                'PublishedDate'=>$books->getPublishedDate()->format('Y-m-d'),
                'status'=>$books->getStatus()->value
            ];
        },$books);
        return $this->json(
                ['status'=>'success',
                'data'=>$responseData]);
    }
    return $this->json(['status'=>'Book not Found'],Response::HTTP_OK);
    }
    // Get Book by Id
    #[Route('/book/{id}/',methods:['GET'])]
    public function getBookById(int $id): JsonResponse
    {
        $book = $this->bookService->getBookById($id);

        if(!$book)
        {
            return $this->json(['status'=>'Book not Found'],Response::HTTP_NOT_FOUND);
        }
        $responseData=[
            'status' => 'success',
            'data' => [
            'id'=>$book->getId(),
            'title'=>$book->getTitle(),
            'author'=>$book->getAuthor(),
            'isbn'=>$book->getIsbn(),
            'PublishedDate'=>$book->getPublishedDate()->format('Y-m-d'),
            'status'=>$book->getStatus()->value
            ]
        ];
        return $this->json($responseData);
    }
    #[Route('/book/delete/{id}',methods:['DELETE'])]
    public function deleteBook(int $id): JsonResponse
    {

        try {
            $checkBook = $this->bookService->getBookById($id);
            if(empty($checkBook))
            {            
                throw new \Exception("Book does not exist");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message"=>$e->getMessage()],JsonResponse::HTTP_NOT_FOUND);
            
        }

        $book = $this->bookService->getBookById($id);
        $book->setDeletionStatus(DeletionStatus::DELETED);
        
        try {
            $this->bookService->saveBook($book);
            return new JsonResponse(["message"=>"Book Deleted Successfully:"],JsonResponse::HTTP_OK);
        } catch (ValidatorException $e) {
            return new JsonResponse(["message"=>"Unexpected Error:".$e->getMessage()],JsonResponse::HTTP_CONFLICT);
        } catch (\Exception $e) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
    }
}
