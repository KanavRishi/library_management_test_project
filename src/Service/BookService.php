<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Book;
use App\Repository\BookRepository;
use App\Enum\Status;
use App\ValueObject\Title;
use App\ValueObject\Isbn;
use App\ValueObject\Author;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

class BookService
{
    private $entityManager;
    private $validator;
    private BookRepository $bookRepository;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, BookRepository $bookRepository)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->bookRepository = $bookRepository;
    }

    // method for creating book object 
    public function createBook(string $author, string $title, string $isbn, string $status, \DateTime $publishedDate): Book
    {
        $book = new Book(new Author($author), new Title($title), new Isbn($isbn), $publishedDate, $status);

        return $book;
    }

    // method for checking validations and persisting book
    public function saveBook(Book $book): bool
    {
        $violations = $this->validator->validate($book);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new ValidatorException(implode(', ', $errors));
        }

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        return true;
    }

    // method for book listing if the book is not deleted
    public function listBooks(): array
    {
        return $this->bookRepository->createQueryBuilder('b')
            ->where("b.status != 'deleted'")
            ->getQuery()
            ->getResult();
    }

    // method for fetching single book by id
    public function getBookById(int $id): ?Book
    {
        $book = $this->bookRepository->createQueryBuilder('b')
            ->where('b.id = ' . $id)
            ->andWhere("b.status != 'deleted'")
            ->getQuery()
            ->getOneOrNullResult();
        if (!$book) {
            return null;
        }
        return $book;
    }

    // update book by id
    public function updateBook(int $id, array $data): Book
    {
        $book = $this->getBookById($id);
        $book->setTitle(new Title($data['title']));
        $book->setAuthor(new Author($data['author']));
        $book->setIsbn(new Isbn($data['isbn']));
        $book->setpublishedDate(\DateTime::createFromFormat('Y-m-d', $data['publisheddate']));
        $book->setStatus(Status::from($data['status']));

        return $book;
    }

    // Method for updating Book status from available to borrowed
    public function updateBookStatus(Book $book): bool
    {
        $status = Status::from("borrowed");
        $book->setStatus($status);
        $this->entityManager->persist($book);
        $this->entityManager->flush();
        return true;
    }

    // method for checking validation errors
    public function validate(Book $book): void
    {
        $violations = $this->validator->validate($book);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new ValidatorException(implode(', ', $errors));
        }
    }

    // Method for changing book status when a book is returned
    public function changeBookStatus(Book $book): bool
    {
        $book->setStatus(Status::from('available'));
        $this->entityManager->persist($book);
        $this->entityManager->flush();
        return true;
    }
    // Fetch Book Title record
    public function getBookTitle($id)
    {
        $bookTitle = $this->bookRepository->find($id);
        return $bookTitle->getTitle();
    }
}
?>