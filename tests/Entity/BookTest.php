<?php

namespace App\Tests\Entity;

use App\Entity\Book;
use App\ValueObject\Title;
use App\ValueObject\Author;
use App\ValueObject\Isbn;
use App\Enum\Status;
use App\Enum\DeletionStatus;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    public function testGettersAndSetters()
    {
        // Initialize the required objects for the constructor
        $title = new Title("The Dark Knight");
        $author = new Author("Kanav");
        $isbn = new Isbn("8181781234");
        $publishedDate = new \DateTime('1995-03-10');

        // Create the Book object with the required constructor parameters
        $book = new Book($author, $title, $isbn, $publishedDate);

        // Test getting the title
        // dd();
        $this->assertSame($title->getValue(), $book->getTitle());

        // Test getting the author
        $this->assertSame($author->getName(), $book->getAuthor());

        // Test getting the ISBN
        $this->assertSame($isbn->getValue(), $book->getIsbn());

        // Test setting and getting the status
        $status = Status::AVAILABLE;
        $book->setStatus($status);
        $this->assertSame($status, $book->getStatus());

        // Test getting the published date
        $this->assertSame($publishedDate, $book->getPublishedDate());

        // Test setting and getting the created_at date
        $createdAt = new \DateTimeImmutable();
        $book->setCreatedAt($createdAt);
        $this->assertSame($createdAt, $book->getCreatedAt());

        // Test setting and getting the updated_at date
        $updatedAt = new \DateTimeImmutable();
        $book->setUpdatedAt($updatedAt);
        $this->assertSame($updatedAt, $book->getUpdatedAt());

        // Test setting and getting the deletion status
        $deletionStatus = DeletionStatus::ACTIVE;
        $book->setDeletionStatus($deletionStatus);
        $this->assertSame($deletionStatus, $book->getDeletionStatus());
    }
}
