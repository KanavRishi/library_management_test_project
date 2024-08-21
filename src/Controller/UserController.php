<?php

namespace App\Controller;

use App\Service\UserService;
use App\Service\BookService;
use App\Service\BorrowService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\Role;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use App\Enum\DeletionStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\BorrowRepository;

class UserController extends AbstractController
{
    private UserService $userService;
    private BookService $bookService;
    private BorrowService $borrowService;
    private BorrowRepository $borrowRepository;

    public function __construct(UserService $userService, BookService $bookService, BorrowService $borrowService, BorrowRepository $borrowRepository)
    {
        $this->userService = $userService;
        $this->bookService = $bookService;
        $this->borrowService = $borrowService;
        $this->borrowRepository = $borrowRepository;
    }
    //  Create User controller
    #[Route('/user', methods: ['POST'], name: 'create_user')]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        //check wheter the variable is set or not
        if (!isset($data['name'], $data['email'], $data['role'], $data['password'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Please provide all required data'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        // Hashing the password
        $password = password_hash($data['password'], PASSWORD_BCRYPT);

        // create user object
        try {
            $user = $this->userService->createUser($data['name'], $data['email'], $password, $data['role']);
            if (!$user) {
                throw new \Exception("Unexpected Error");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => "Unexpected Error:" . $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        // validate role 
        try {
            $role = Role::from($data['role']);
        } catch (\ValueError $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid Role value'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // save user and check for exceptions
        try {
            $this->userService->saveUser($user);
            return new JsonResponse([
                'status' => 'success',
                'message' => 'User created successfully'
            ], JsonResponse::HTTP_CREATED);
        } catch (UniqueConstraintViolationException $e) {

            return new JsonResponse([
                'status' => 'error',
                'message' => 'A User with this email already exists.'
            ], JsonResponse::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    // Update User
    #[Route('/user/{id}', methods: ["PUT"])]
    public function updateUser(Request $request, $id): JsonResponse
    {
        // check whether id is integer and positive value
        try {
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                throw new \InvalidArgumentException('Invalid ID provided.');
            }
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // get content
        $data = json_decode($request->getContent(), true);

        // check if the user exist or not If exist the get user data by id
        try {
            $user = $this->userService->getUserById($id);
            if (!$user) {
                throw new \Exception("User not found");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => "Unexpected Error:" . $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }
        // check variables exist or not
        if (!isset($data['name'], $data['email'], $data['role'], $data['password'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Please Input all values'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // check role value
        try {
            $role = Role::from($data['role']);
        } catch (\ValueError $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid role value'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // create user object using updateUser method
        try {
            $userData = $this->userService->updateUser($id, $data);
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => "error",
                "message" => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // save user and check exception
        try {
            $this->userService->saveUser($userData);
            return new JsonResponse([
                "status" => "success",
                "message" => "User Updated Successfully"
            ], JsonResponse::HTTP_CREATED);
        } catch (UniqueConstraintViolationException $e) {

            return new JsonResponse([
                'status' => 'error',
                'message' => 'A User with this email already exists.'
            ], JsonResponse::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return new JsonResponse([
                "status" => "error",
                "message" => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    // list user method
    #[Route('/user', methods: ["GET"], name: 'list_user')]
    public function listUser(): JsonResponse
    {
        // List User object
        $users = $this->userService->listusers();
        if ($users) {
            $responseData = array_map(function ($users) {
                return [
                    'id' => $users->getId(),
                    'name' => $users->getName(),
                    'email' => $users->getEmail(),
                    'role' => $users->getRole()->value
                ];
            }, $users);
            return $this->json(
                [
                    'status' => 'success',
                    'data' => $responseData
                ]
            );
        }
        return $this->json(['status' => 'User not Found'], JsonResponse::HTTP_NOT_FOUND);
    }

    #[Route('/user/{id}/', methods: ['GET'])]
    public function getUserById($id): JsonResponse
    {
        // check whether id is numeric and positive integer
        try {
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                throw new \InvalidArgumentException('Invalid ID provided.');
            }
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // get user by id and check user if exist
        try {
            $user = $this->userService->getUserById($id);
            if (!$user) {
                return $this->json(['status' => 'User not Found'], JsonResponse::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
        // dd($user);
        $responseData = [
            'status' => 'success',
            'data' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'password' => $user->getPassword(),
                'role' => $user->getRole()->value
            ]
        ];
        return $this->json($responseData);
    }
    #[Route('/user/delete/{id}', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        // check whether id is numeric and positive integer
        try {
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                throw new \InvalidArgumentException('Invalid ID provided.');
            }
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // check wheter user exist or not
        try {
            $user = $this->userService->getUserById($id);
            if (empty($user)) {
                throw new \Exception("User does not exist");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }

        $user->setDeletionStatus(DeletionStatus::DELETED);

        try {
            $this->userService->saveUser($user);
            return new JsonResponse(["message" => "User Deleted Successfully:"], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(["message" => "Unexpected Error:" . $e->getMessage()], JsonResponse::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    // Borrow Book
    #[Route('/borrow', methods: ['PUT'])]
    public function borrowWhenAvailable(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // check bookid and userid to be integer and positive value
        try {
            if (!is_numeric($data['userid']) || intval($data['userid']) != $data['userid'] || $data['userid'] <= 0) {
                throw new \InvalidArgumentException('Invalid User ID provided.');
            }
            if (!is_numeric($data['bookid']) || intval($data['bookid']) != $data['bookid'] || $data['bookid'] <= 0) {
                throw new \InvalidArgumentException('Invalid Book ID provided.');
            }
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // getBookById check if the book exist or not
        try {
            $book = $this->bookService->getBookById($data['bookid']);
            // dd($book);
            if (!$book) {
                throw new \Exception("Book not found");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        } catch (UniqueConstraintViolationException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Book Already Borrowed.'
            ], JsonResponse::HTTP_CONFLICT);
        }

        // check if the user exist or not
        try {
            $user = $this->userService->getUserById($data['userid']);
            if (!$user) {
                throw new \Exception("User not found");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }

        // check if the book is already borrowed or not
        try {
            if ($book->getStatus()->value == "borrowed") {
                throw new \Exception("Book Already Borrowed");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_OK);
        }

        // update book status to borrowed from available
        $book_stat = $this->bookService->updateBookStatus($book);
        if ($book_stat) {

            $check = $this->borrowService->borrowBook($data);
            if ($check) {
                return new JsonResponse([
                    "status" => "Book Borrowed Successfully"
                ], JsonResponse::HTTP_CREATED);
            }
        }
    }

    // Return book controller
    #[Route('/borrow/return/{id}', methods: ['POST'], name: 'return_book')]
    public function returnBook(int $id): JsonResponse
    {
        // check bookid and userid to be integer and positive value
        try {
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                throw new \InvalidArgumentException('Invalid User ID provided.');
            }
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // check if the borrow record exist or not
        try {
            $borrow = $this->borrowRepository->find($id);
            if (!$borrow) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Borrow record not found'
                ], JsonResponse::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }

        // Return Book logic
        $this->borrowService->returnBook($borrow);
        try {
            $getBookId = $this->bookService->getBookById($borrow->getBookid()->getId());
            if (!($getBookId)) {
                throw new \Exception("Book Not Found");
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        // change status code
        try {
            $changeStatus = $this->bookService->changeBookStatus($getBookId);
            if (!($changeStatus)) {
                throw new \Exception("Book Not Found");
            }
            return new JsonResponse([
                'status' => 'success',
                'message' => "Book Returned Successfully"
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'success',
                'message' => $e->getMessage()
            ]);
        }
    }
}
