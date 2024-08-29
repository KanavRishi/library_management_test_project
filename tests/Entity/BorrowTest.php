<?php

namespace App\Tests\Entity;

use App\Entity\Borrow;
use App\Entity\User;
use App\Entity\Book;
use App\ValueObject\Title;
use App\ValueObject\Author;
use App\ValueObject\Isbn;
use App\ValueObject\Name;
use App\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Faker\Factory;

class BorrowTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::$kernel->getContainer()->get("doctrine")->getManager();
    }
    
    public function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testBorrowEntity(): void
    {
        $faker = Factory::create();

        // Create User and Book objects
        $name = new Name("Kanav Rishi");
        $email = new Email($faker->email());
        $password = 'password123';
        $user = new User($name, $email, $password);

        $title = new Title("The Dark Knight Rises");
        $author = new Author("KanavR");
        $isbn = new Isbn($faker->isbn13());
        $publishedDate = new \DateTime('1995-03-10');
        $book = new Book($author, $title, $isbn, $publishedDate);

        // Persist and flush the entities to the database
        $this->entityManager->persist($user);
        $this->entityManager->persist($book);
        $this->entityManager->flush();

        // Create a Borrow object
        $borrow = new Borrow();
        $borrow->setUserid($user);
        $borrow->setBookid($book);
        $borrow->setBorrowDate(new \DateTime());

        $this->entityManager->persist($borrow);
        $this->entityManager->flush();

        // Retrieve the Borrow entity from the database
        $retrievedBorrow = $this->entityManager->getRepository(Borrow::class)->find($borrow->getId());

        // Assert that the retrieved entity is the same as the stored one
        $this->assertEquals($borrow->getUserid()->getName(), $retrievedBorrow->getUserid()->getName());
        $this->assertEquals($borrow->getBookid()->getTitle(), $retrievedBorrow->getBookid()->getTitle());
        $this->assertEquals($borrow->getBorrowDate(), $retrievedBorrow->getBorrowDate());
    }
}
