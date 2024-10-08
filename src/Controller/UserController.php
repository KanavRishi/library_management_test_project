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
        try {
            if (!isset($data['name'], $data['email'], $data['role'], $data['password'])) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"Please input all values"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }
            // Hashing the password
            $password = password_hash($data['password'], PASSWORD_BCRYPT);

            // validate role 

            $user = $this->userService->createUser($data['name'], $data['email'], $password, $data['role']);
            if (!$user) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"User not created"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }
            // check role value    
            $validRoles = array_map(fn($role) => $role->value, Role::cases());
            if (!in_array($data['role'], $validRoles, true)) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"Please input all values"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // save user and check for exceptions
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
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], JsonResponse::HTTP_BAD_REQUEST);
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
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"Invalid Id provided"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // get content
            $data = json_decode($request->getContent(), true);

            // check if the user exist or not If exist the get user data by id
            $user = $this->userService->getUserById($id);
            if (!$user) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"User not found"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // check variables exist or not
            if (!isset($data['name'], $data['email'], $data['role'], $data['password'])) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"Please Input all values"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // check role value    
            $validRoles = array_map(fn($role) => $role->value, Role::cases());
            if (!in_array($data['role'], $validRoles, true)) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"Input role values"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // create user object using updateUser method

            // save user and check exception    
            $userData = $this->userService->updateUser($id, $data);
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
                return new JsonResponse([
                            'status'=> "error",
                            "message"=>"Invalid Id provided"
                        ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $user = $this->userService->getUserById($id);
            if (!$user) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"User not found"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
        $responseData = [
            'status' => 'success',
            'data' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole()->value
                ]
        ];
        return $this->json($responseData);
    }
    #[Route('/user/delete/{id}', methods: ['DELETE'])]
    public function deleteUser($id): JsonResponse
    {
        // check whether id is numeric and positive integer
        try {
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"User not found"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // check wheter user exist or not
            $user = $this->userService->getUserById($id);
            if (empty($user)) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"User does not exist"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $user->setDeletionStatus(DeletionStatus::DELETED);

            $this->userService->saveUser($user);
            return new JsonResponse(["message" => "User Deleted Successfully:"], JsonResponse::HTTP_OK);
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
                    return new JsonResponse([
                            'status'=> "error",
                            "message"=>"Invalid User Id provided"
                        ], JsonResponse::HTTP_BAD_REQUEST);
            }
            if (!is_numeric($data['bookid']) || intval($data['bookid']) != $data['bookid'] || $data['bookid'] <= 0) {
                    return new JsonResponse([
                            'status'=> "error",
                            "message"=>"Invalid Book Id provided"
                        ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // getBookById check if the book exist or not

            $book = $this->bookService->getBookById($data['bookid']);
            if (!$book) {
                return new JsonResponse([
                    'status'=> "error",
                    "message"=>"Book not found"
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
            // check if the user exist or not

            $user = $this->userService->getUserById($data['userid']);
            if (!$user) {
                return new JsonResponse([
                    'status'=> "error",
                    "message"=>"User not found"
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // check if the book is already borrowed or not
            if ($book->getStatus()->value == "borrowed") {
                    return new JsonResponse([
                        'status'=> "error",
                        "message"=>"Book Aready Borrowed"
                    ], JsonResponse::HTTP_BAD_REQUEST);
            }
        } catch (UniqueConstraintViolationException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Book Already Borrowed.'
            ], JsonResponse::HTTP_CONFLICT);
        } catch (\Exception $e) {
            return new JsonResponse(["message" => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        // update book status to borrowed from available
        $book_stat = $this->bookService->updateBookStatus($book);
        if ($book_stat) {

            $check = $this->userService->borrowBook($data);
            if ($check) {
                return new JsonResponse([
                    "status" => "Book Borrowed Successfully"
                ], JsonResponse::HTTP_CREATED);
            }
        }
    }

    // Return book controller
    #[Route('/borrow/return/{id}', methods: ['POST'], name: 'return_book')]
    public function returnBook($id): JsonResponse
    {
        // check bookid and userid to be integer and positive value
        try {
            if (!is_numeric($id) || intval($id) != $id || $id <= 0) {
                return new JsonResponse([
                    'status'=> "error",
                    "message"=>"Invalid Id provided"
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // check if the borrow record exist or not

            $borrow = $this->borrowService->getBorrowBookById($id);
            // dd();
            if (!$borrow) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Borrow record not found'
                ], JsonResponse::HTTP_NOT_FOUND);
            }
            
            // Return Book logic
            $this->userService->returnBook($borrow);
            $getBookId = $this->bookService->getBookById($borrow->getId());
            if (!($getBookId)) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"Book not found"
                    ], JsonResponse::HTTP_NOT_FOUND);
            }
            // change status code
            $changeStatus = $this->bookService->changeBookStatus($getBookId);
            if (!($changeStatus)) {
                return new JsonResponse([
                        'status'=> "error",
                        "message"=>"Book not found"
                    ], JsonResponse::HTTP_NOT_FOUND);
            }
            return new JsonResponse([
                'status' => 'success',
                'message' => "Book Returned Successfully"
            ], JsonResponse::HTTP_OK);
        }  catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
}
