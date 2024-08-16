<?php
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BorrowRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\BorrowService;
use App\Entity\Borrow;
use App\Enum\Status;
class BorrowServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private BorrowRepository $borrowRepository;
    private BorrowService $borrowService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->borrowRepository = $this->createMock(BorrowRepository::class);

        $this->borrowService = new BorrowService(
            $this->entityManager,
            $this->validator,
            $this->borrowRepository
        );
    }

    public function testBorrowBook()
    {
        $borrow = $this->createMock(Borrow::class);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($borrow);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->borrowService->borrowBook($borrow);

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