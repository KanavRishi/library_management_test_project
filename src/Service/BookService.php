<?php
namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Book;
use App\Entity\Borrow;
use App\Repository\BookRepository;
use App\Enum\Status;
use phpDocumentor\Reflection\Types\Integer;
use App\ValueObject\Title;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\ValueObject\Isbn;
use App\ValueObject\Author;
use App\Enum\DeletionStatus;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

class BookService
{
    private $entityManager;
    private $validator;
    private BookRepository $bookRepository;

    public function __construct(EntityManagerInterface $entityManager,ValidatorInterface $validator,BookRepository $bookRepository)
    {
        $this->entityManager=$entityManager;
        $this->validator=$validator;
        $this->bookRepository=$bookRepository;
    }

    public function createBook(string $author,string $title,string $isbn,string $status,\DateTime $publishedDate): Book
    {

        $book = new Book(new Author($author),new Title($title),new Isbn($isbn),$publishedDate,$status);

        return $book;
    }
 
    public function saveBook(Book $book): bool
    {   
        // dd($book);
        $violations = $this->validator->validate($book);
        // echo count($violations);
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

    public function listBooks(): array
    {
        return $this->bookRepository->createQueryBuilder('b')
        ->where('b.status != :deletedStatus')
        ->setParameter('deletedStatus', 'deleted')
        ->getQuery()
        ->getResult();
    }

    public function getBookById(int $id): ?Book
    {
        $book = $this->bookRepository->createQueryBuilder('b')
        ->where('b.id = :id')
        ->andWhere('b.status != :activeStatus')
        ->setParameter('id', $id)
        ->setParameter('activeStatus', 'deleted')
        ->getQuery()
        ->getOneOrNullResult();
        if (!$book) {
            return null;
        }
        return $book;
    }

    public function updateBook(int $id,array $data): Book
    {
        $book = $this->getBookById($id);
        $book->setTitle(new Title($data['title']));
        $book->setAuthor(new Author($data['author']));
        $book->setIsbn(new Isbn($data['isbn']));
        $book->setpublishedDate(\DateTime::createFromFormat('Y-m-d',$data['publisheddate']));
        $book->setStatus(Status::from($data['status']));
        
        return $book;
    }
    public function updateBookStatus(Book $book): bool
    {
        $status = Status::from("borrowed");
        $book->setStatus($status);
        // dd($book);
        $this->entityManager->persist($book);
        $this->entityManager->flush();
        return true;
    }
    public function checkDuplBook(Isbn $isbn): bool
    {
        $isbnValue = $isbn->getValue();
        $check_dupl = $this->bookRepository->findOneBy(['isbn.value' => $isbnValue]);
        if($check_dupl)
        {
            return true;
        }
        return false;
        // return true;
    }
    public function validate(Book $book): void
    {
        $violations = $this->validator->validate($book);
        // dd($violations);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new ValidatorException(implode(', ', $errors));
        }
    }
    public function changeBookStatus(Book $book): bool
    {
        // dd($book);
        $book->setStatus(Status::from('available'));
        $this->entityManager->persist($book);
        $this->entityManager->flush();
        return true;
    }
    
}
?>