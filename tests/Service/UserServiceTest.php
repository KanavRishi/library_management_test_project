<?php
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\UserService;
use App\Entity\User;
use App\Enum\Role;
use App\Service\BookService;
use App\ValueObject\Name;
use App\ValueObject\Email;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidatorException;

class UserServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private UserRepository $userRepository;
    private UserService $userService;
    private BookService $bookService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->bookService = $this->createMock(BookService::class);

        $this->userService = new UserService(
            $this->entityManager,
            $this->validator,
            $this->userRepository,
            $this->bookService
        );
    }

    public function testCreateUser()
    {
        $name = 'John Doe';
        $email = 'john.doe@example.com';
        $password = 'password';
        $role = 'Member';

        $user = $this->userService->createUser($name, $email, $password, $role);
        // dd($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($password, $user->getPassword());
        $this->assertEquals($role, $user->getRole()->value);
    }

    public function testSaveUserWithValidationErrors()
    {
        $user = $this->createMock(User::class);

        $violations = new ConstraintViolationList([
            new ConstraintViolation('Error', '', [], '', '', '')
        ]);
        $this->validator
            ->method('validate')
            ->willReturn($violations);

        $this->expectException(ValidatorException::class);

        $this->userService->saveUser($user);
    }

    public function testSaveUser()
    {
        $user = $this->createMock(User::class);

        $this->validator->method('validate')->willReturn(new ConstraintViolationList([]));

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->userService->saveUser($user);
    }

    public function testUpdateUser()
    {
        $id = 4;
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => 'newpassword@123',
            'role' => 'Admin'
        ];
        // dd($id);
        $user = $this->createMock(User::class);
        $this->userRepository
            ->method('findOneBy')
            ->with(['id' => '4', 'deletionStatus' => 'active'])
            ->willReturn($user);

        $user->expects($this->once())
            ->method('setName')
            ->with(new Name($data['name']));

        $user->expects($this->once())
            ->method('setEmail')
            ->with(new Email($data['email']));

        $user->expects($this->once())
            ->method('setRole')
            ->with(Role::from($data['role']));

        $updatedUser = $this->userService->updateUser($id, $data);
        // dd($data);
        $this->assertInstanceOf(User::class, $updatedUser);
    }

    public function testGetUserById()
    {
        $id = 1;
        $user = $this->createMock(User::class);
        $this->userRepository
            ->method('findOneBy')
            ->with(['id' => $id, 'deletionStatus' => 'active'])
            ->willReturn($user);

        $result = $this->userService->getUserById($id);

        $this->assertSame($user, $result);
    }

    public function testListUsers()
    {
        $users = [$this->createMock(User::class)];
        $this->userRepository
            ->method('findAll')
            ->willReturn($users);

        $result = $this->userService->listUsers();

        $this->assertIsArray($result);
        $this->assertContainsOnlyInstancesOf(User::class, $result);
    }
}
