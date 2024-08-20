<?php

namespace App\Tests\Service;

use App\Service\BookService;
use App\Entity\Book;
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
        $createdAt = new \DateTime();

        $book = $this->bookService->createBook($author, $title, $isbn, $status, $publishedDate, $createdAt);
        // dd($author);

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

        $violations = new ConstraintViolationList([
            $this->createMock(\Symfony\Component\Validator\ConstraintViolation::class)
        ]);

        $this->validator->method('validate')->willReturn($violations);

        $this->expectException(ValidatorException::class);

        $this->bookService->saveBook($book);
    }

    public function testListBooks(): void
    {
        $books = [$this->createMock(Book::class)];

        $this->bookRepository
            ->method('findAll')
            ->willReturn($books);

        $result = $this->bookService->listBooks();

        $this->assertSame($books, $result);
    }

    public function testGetBookById(): void
    {
        $book = $this->createMock(Book::class);

        $this->bookRepository
            ->method('findOneBy')
            ->with(['id' => 1, 'deletionStatus' => 'active'])
            ->willReturn($book);

        $result = $this->bookService->getBookById(1);

        $this->assertSame($book, $result);
    }

    public function testDeleteBook(): void
    {
        $book = $this->createMock(Book::class);

        $this->bookRepository
            ->method('find')
            ->with(1)
            ->willReturn($book);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($book);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->bookService->deleteBook(1);

        $this->assertTrue($result);
    }

    public function testDeleteBookNotFound(): void
    {
        $this->bookRepository
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $result = $this->bookService->deleteBook(1);

        $this->assertFalse($result);
    }
}
