<?php

namespace App\Tests\Entity;

use App\Entity\Borrow;
use App\Entity\User;
use App\Entity\Book;
use Symfony\Contracts\Cache\CacheInterface;
use PHPUnit\Framework\TestCase;

class BorrowTest extends TestCase
{
    public function testBorrowEntity()
    {
        $cache = $this->createMock(CacheInterface::class);

        // Set up Borrow entity
        $borrow = new Borrow();

        $user = $this->createMock(User::class);
        $borrow->setUserid($user);
        $this->assertSame($user, $borrow->getUserid());

        $book = $this->createMock(Book::class);
        $borrow->setBookid($book);
        $this->assertSame($book, $borrow->getBookid());

        $borrowDate = new \DateTime('2024-08-01 10:00:00');
        $borrow->setBorrowDate($borrowDate);
        $this->assertSame($borrowDate, $borrow->getBorrowDate());

        $returnDate = new \DateTime('2024-08-10 10:00:00');
        $borrow->setReturnDate($returnDate);
        $this->assertSame($returnDate, $borrow->getReturnDate());

        $borrow->updatedTimestamps();
        $this->assertNotNull($borrow->getBorrowDate());
        $this->assertNotNull($borrow->getReturnDate());

        // Store Borrow entity in cache
        $cacheKey = 'borrow_' . $borrow->getUserid()->getId() . '_' . $borrow->getBookid()->getId();
        $cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($borrow);

        // Retrieve from cache and assert
        $cachedBorrow = $cache->get($cacheKey, function () use ($borrow) {
            return $borrow;
        });
        $this->assertSame($borrow, $cachedBorrow);
    }
}
