<?php

namespace App\Tests\Service;

use App\Service\BookService;
use App\Entity\Book;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use App\ValueObject\Isbn;
use App\ValueObject\Title;
use App\ValueObject\Author;
use Symfony\Component\Validator\ConstraintViolationList;
use PHPUnit\Framework\MockObject\MockObject;

class BookServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private BookRepository $bookRepository;

    private BookService $bookService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);

        $this->bookService = new BookService(
            $this->entityManager,
            $this->validator,
            $this->bookRepository
        );
    }

    public function testCreateBook(): void
    {
        $author = 'John Doe';
        $title = 'A Great Book';
        $isbn = '1234567890123';
        $status = 'available';
        $publishedDate = new \DateTime('2024-01-01');

        $book = $this->bookService->createBook($author, $title, $isbn, $status, $publishedDate);
        // dd($book);

        $this->assertInstanceOf(Book::class, $book);
        $this->assertEquals($author, $book->getAuthor());
        $this->assertEquals($title, $book->getTitle());
        $this->assertEquals($isbn, $book->getIsbn());
    }

    public function testSaveBookSuccess(): void
    {
        $book = $this->createMock(Book::class);

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($book);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->bookService->saveBook($book);

        $this->assertTrue($result);
    }

    public function testSaveBookValidationFails(): void
    {
        $book = $this->createMock(Book::class);

        // Create a mock ConstraintViolation
        $violation = $this->createMock(\Symfony\Component\Validator\ConstraintViolation::class);

        // Set up the validator to return a list with the violation
        $violations = new \Symfony\Component\Validator\ConstraintViolationList([$violation]);

        $this->validator
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidatorException::class);

        $this->bookService->saveBook($book);
    }


    public function testListBooks(): void
    {
        $book = $this->createMock(Book::class);
        $books = [$book];
        
        // Mock the QueryBuilder, Query, and other chained calls
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);

        // Mock the repository method that returns the QueryBuilder
        $this->bookRepository
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturn($queryBuilder);

        // Mock the chainable methods of the QueryBuilder
        $queryBuilder
            ->method('where')
            ->with('b.status != :deletedStatus')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->method('setParameter')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->method('getQuery')
            ->willReturn($query);

        // Mock the getResult to return the list of books
        $query
            ->method('getResult')
            ->willReturn($books);

        $result = $this->bookService->listBooks();

        $this->assertSame($books, $result);
    }

    public function testGetBookById(): void
    {
        $book = $this->createMock(Book::class);
        $books = [$book];
        
        // Mock the QueryBuilder, Query, and other chained calls
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);

        // Mock the repository method that returns the QueryBuilder
        $this->bookRepository
            ->method('createQueryBuilder')
            ->with('b')
            ->willReturn($queryBuilder);

        // Mock the chainable methods of the QueryBuilder
        $queryBuilder
            ->method('where')
            ->with('b.id = :id')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->method('andWhere')
            ->with('b.status != :activeStatus')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->method('setParameter')
            ->willReturn($queryBuilder);

        $queryBuilder
            ->method('getQuery')
            ->willReturn($query);

        // Mock the getOneOrNullResult to return the book
        $query
            ->method('getOneOrNullResult')
            ->willReturn($book);

        $result = $this->bookService->getBookById(55);

        $this->assertSame($book, $result);
    }

}
