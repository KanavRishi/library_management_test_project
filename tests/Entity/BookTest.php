<?php
namespace App\Tests\Entity;

use App\Entity\Book;
use App\ValueObject\Title;
use App\ValueObject\Author;
use App\ValueObject\Isbn;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Faker\Factory;

class BookTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::$kernel->getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // close up after the test 
        $this->entityManager->close();
        $this->entityManager = null; 
    }

    public function testBookPersistence(): void
    {
        $faker = Factory::create();
        // Create a Book object
        $title = new Title("The Dark Knight Rises");
        $author = new Author("Kanav Singh");
        $isbn = new Isbn($faker->isbn13());
        $publishedDate = new \DateTime('1995-03-10');
        $book = new Book($author, $title, $isbn, $publishedDate);

        // Insert the book to the database
        $this->entityManager->persist($book);
        $this->entityManager->flush();

        // Retrieve the book from the database to verify it was inserted
        $retrievedBook = $this->entityManager->getRepository(Book::class)->find($book->getId());
        
        // Assert that the retrieved book is the same as the inserted book
        $this->assertSame($book, $retrievedBook);
    }
}