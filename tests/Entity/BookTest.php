<?php
namespace App\Tests\Entity;

use App\Entity\Book;
use App\ValueObject\Title;
use App\ValueObject\Author;
use App\ValueObject\Isbn;
use Symfony\Contracts\Cache\CacheInterface;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    public function testStoreBookInCache(): void
    {
        // Create a mock for the CacheInterface
        $cache = $this->createMock(CacheInterface::class);

        // Create a Book object
        $title = new Title("The Dark Knight Rises");
        $author = new Author("Kanav");
        $isbn = new Isbn("7181781235");
        $publishedDate = new \DateTime('1995-03-10');
        $book = new Book($author, $title, $isbn, $publishedDate);

        // Simulate storing and retrieving the book in cache
        $cacheKey = 'book_' . $book->getId();
        // Configure the cache mock to expect the 'get' method call
        $cache->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo($cacheKey),
                $this->callback(function ($callback) use ($book) {
                    // Simulate the cache storing the book if it's not found
                    return $callback() === $book;
                })
            )
            ->willReturn($book);

        // Use the cache's get method to either retrieve or store the book
        $retrievedBook = $cache->get($cacheKey, function () use ($book) {
            return $book;
        });
        // Assert that the retrieved book is the same as the stored book
        $this->assertSame($book, $retrievedBook);
    }
}
