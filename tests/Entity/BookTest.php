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

    public function testBook(): void
    {
        $faker = Factory::create();

        // Step 1: Create a Book object
        $title = new Title($faker->sentence());
        $author = new Author("Kanav Singh");
        $isbn = new Isbn($faker->isbn13());
        $publishedDate = new \DateTime('1995-03-10');
        $book = new Book($author, $title, $isbn, $publishedDate);

        // Step 2: Insert the book into the database
        $this->entityManager->persist($book);
        $this->entityManager->flush();

        // Step 3: Retrieve the book from the database to verify it was inserted
        $retrievedBook = $this->entityManager->getRepository(Book::class)->findByTitle($title);

        // Assert that the retrieved book is the same as the inserted book
        $this->assertSame($book, $retrievedBook);

        // Step 4: Update the book's title and persist the changes
        $newTitle = new Title($faker->sentence());
        $retrievedBook->setTitle($newTitle);
        $this->entityManager->flush();

        // Retrieve the updated book and verify the title was updated
        $updatedBook = $this->entityManager->getRepository(Book::class)->findByTitle($newTitle);
        $this->assertEquals($newTitle, $updatedBook->getTitle());

        // Step 5: Delete the book
        $this->entityManager->remove($updatedBook);
        $this->entityManager->flush();

        // Verify that the book no longer exists in the database
        $deletedBook = $this->entityManager->getRepository(Book::class)->findByTitle($title);
        $this->assertNull($deletedBook);
    }
}