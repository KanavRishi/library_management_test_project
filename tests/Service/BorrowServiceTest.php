<?php
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BorrowRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\BorrowService;
use App\Entity\Borrow;
use App\Entity\User;
use App\Entity\Book;
use App\Service\UserService;
use App\Service\BookService;
use App\Enum\Status;
class BorrowServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private BorrowRepository $borrowRepository;
    private BorrowService $borrowService;
    private UserService $userService;
    private BookService $bookService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->borrowRepository = $this->createMock(BorrowRepository::class);
        $this->userService = $this->createMock(UserService::class);
        $this->bookService = $this->createMock(bookService::class);

        $this->borrowService = new BorrowService(
            $this->entityManager,
            $this->validator,
            $this->borrowRepository,
            $this->userService,
            $this->bookService
        );
    }

    public function testBorrowBook(): void
    {
        $data = [
            'userid' => 1,
            'bookid' => 2,
        ];

        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);
        $borrow = $this->createMock(Borrow::class);

        $this->userService
            ->expects($this->once())
            ->method('getUserById')
            ->with($data['userid'])
            ->willReturn($user);

        $this->bookService
            ->expects($this->once())
            ->method('getBookById')
            ->with($data['bookid'])
            ->willReturn($book);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Borrow::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->borrowService->borrowBook($data);

        $this->assertTrue($result);
    }

    public function testReturnBook()
    {
        $borrow = $this->createMock(Borrow::class);
        $currentDate = new \DateTimeImmutable('now');

        $borrow
            ->expects($this->once())
            ->method('setReturnDate')
            ->with($this->callback(function ($date) use ($currentDate) {
                return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $currentDate->format('Y-m-d');
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($borrow);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->borrowService->returnBook($borrow);

        $this->assertSame($borrow, $result);
    }

    public function testGetBorrowHistory()
    {
        $borrow1 = $this->createMock(Borrow::class);
        $borrow2 = $this->createMock(Borrow::class);
        $borrows = [$borrow1, $borrow2];

        $this->borrowRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($borrows);

        $result = $this->borrowService->getBorrowHistory();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(Borrow::class, $result);
    }
}
?>