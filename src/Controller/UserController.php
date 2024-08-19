<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Borrow;
use App\Service\UserService;
use App\Service\BookService;
use App\Service\BorrowService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Enum\Role;
use App\Enum\Status;
use App\ValueObject\Name;
use App\ValueObject\Email;
use App\Enum\DeletionStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\BorrowRepository;

// use App\Controller\BorrowController;

class UserController extends AbstractController
{
    private UserService $userService;
    private BookService $bookService;
    private BorrowService $borrowService;
    private BorrowRepository $borrowRepository;

    public function __construct(UserService $userService,BookService $bookService,BorrowService $borrowService,BorrowRepository $borrowRepository)
    {
        $this->userService = $userService;
        $this->bookService = $bookService;
        $this->borrowService = $borrowService;
        $this->borrowRepository = $borrowRepository;
    }

    #[Route('/user', methods: ['POST'], name: 'create_user')]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['email'], $data['role'], $data['password'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Please provide all required data'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        $dateTimeImmutable = new \DateTimeImmutable('now');
        $createdAt=new \DateTime($dateTimeImmutable->format('Y-m-d H:i:s'));
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        try{
        $user = $this->userService->createUser($data['name'],$data['email'],$password,$data['role'],$createdAt);
        if(!$user)
        {
            throw new \Exception("Unexpected Error");
        }
        } catch (\Exception $e) {
            return new JsonResponse(["message"=>"Unexpected Error:".$e->getMessage()],JsonResponse::HTTP_BAD_REQUEST);
        }
        try{
            $duplUser = $this->userService->checkDuplUser($data['email']);
            if($duplUser)
            {
                throw new \Exception("User Already Exist");
            }
        }catch (\Exception $e) {
            return new JsonResponse(["message"=>"Unexpected Error:".$e->getMessage()],JsonResponse::HTTP_BAD_REQUEST);
        }
        try {
            $role = Role::from($data['role']);
        } catch (\ValueError $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid Role value'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        $user->setRole($role);
        try {
            $this->userService->saveUser($user);
            return new JsonResponse([
                'status' => 'success',
                'message' => 'User created successfully'
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        
    }

    #[Route('/user/{id}',methods:["PUT"])]
    public function updateUser(Request $request,int $id): JsonResponse
    {
        $data=json_decode($request->getContent(),true);
        try {
        $user=$this->userService->getUserById($id);
        if(!$user)
        {
            throw new \Exception("User not found");
        }
        } catch (\Exception $e){
            return new JsonResponse(["message"=>"Unexpected Error:".$e->getMessage()],JsonResponse::HTTP_NOT_FOUND);
        }
        if (!isset($data['name'], $data['email'], $data['role'], $data['password'])) {
            return new JsonResponse([
                'status'=>'error',
                'message'=>'Please Input all values'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $role = Role::from($data['role']);
        } catch (\ValueError $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid role value'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        try{
            $userData = $this->userService->updateUser($id,$data);
        }catch(\Exception $e){
            return new JsonResponse([
                "status"=>"error",
                "message"=>$e->getMessage()
            ],JsonResponse::HTTP_BAD_REQUEST);
        }
        try{
            $this->userService->saveUser($userData);
            return new JsonResponse([
                "status"=>"success",
                "message"=>"User Updated Successfully"
            ],JsonResponse::HTTP_CREATED);
        } catch(\Exception $e) {
            return new JsonResponse([
                "status"=>"error",
                "message"=>$e->getMessage()
            ],JsonResponse::HTTP_BAD_REQUEST);
        }
    }   

    #[Route('/user',methods:["GET"],name:'list_user')]
    public function listUser(): JsonResponse
    {
        $users = $this->userService->listusers();
        if($users)
        {
        $responseData = array_map(function($users){
            return[
                'id'=>$users->getId(),
                'name'=>$users->getName(),
                'email'=>$users->getEmail(),
                'password'=>$users->getPassword(),
                'role'=>$users->getRole()->value
            ];
        },$users);
        return $this->json(
                ['status'=>'success',
                'data'=>$responseData]);
    }
    return $this->json(['status'=>'User not Found'],JsonResponse::HTTP_NOT_FOUND);
    }

    #[Route('/user/{id}/',methods:['GET'])]
    public function getUserById(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        try{
            if(!$user)
            {
                return $this->json(['status'=>'User not Found'],JsonResponse::HTTP_NOT_FOUND);
            }
        } catch (\Exception $e){
            return new JsonResponse(["message"=>$e->getMessage()],JsonResponse::HTTP_BAD_REQUEST);
        }
        $responseData=[
            'status' => 'success',
            'data' => [
            'id'=>$user->getId(),
            'name'=>$user->getName(),
            'email'=>$user->getEmail(),
            'password'=>$user->getPassword(),
            'role'=>$user->getRole()->value
            ]
        ];
        return $this->json($responseData);
    }
    #[Route('/user/delete/{id}',methods:['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        try {
            $checkUser = $this->userService->getUserById($id);
            if(empty($checkUser))
            {            
                throw new \Exception("User does not exist");
            }
        } catch (\Exception $e) {
            return new JsonResponse(["message"=>$e->getMessage()],JsonResponse::HTTP_NOT_FOUND);
            
        }

        $user = $this->userService->getUserById($id);
        $user->setDeletionStatus(DeletionStatus::DELETED);
        
        try {
            $this->userService->saveUser($user);
            return new JsonResponse(["message"=>"User Deleted Successfully:"],JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(["message"=>"Unexpected Error:".$e->getMessage()],JsonResponse::HTTP_CONFLICT);
        } catch (\Exception $e) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
    }
    
        // Borrow Book
        #[Route('/borrow',methods:['PUT'])]
        public function borrowWhenAvailable(Request $request): JsonResponse
        {
            $data=json_decode($request->getContent(),true);
            
            try{
            $book=$this->bookService->getBookById($data['bookid']);
            // dd($book);
            if(!$book)
            {
                throw new \Exception("Book not found");
            }
            } catch (\Exception $e){
                return new JsonResponse(["message"=>$e->getMessage()],JsonResponse::HTTP_NOT_FOUND);
            }
            try{
            $user=$this->userService->getUserById($data['userid']);
            if(!$user)
            {
                throw new \Exception("User not found");
            }
            } catch (\Exception $e) {
                return new JsonResponse(["message"=>$e->getMessage()],JsonResponse::HTTP_NOT_FOUND);
            }
            try{
            if($book->getStatus()->value=="borrowed")
            {
                throw new \Exception("Book Already Borrowed");
            } 
            } catch (\Exception $e) {
                return new JsonResponse(["message"=>$e->getMessage()],JsonResponse::HTTP_OK);
            }
    
            
            $book_stat=$this->bookService->updateBookStatus($book);
            if($book_stat)
            {
                $user = $this->userService->getUserById($data['userid']);
                $book = $this->bookService->getBookById($data['bookid']);
                $borrow=new Borrow();
                $borrow->setUserid($user);
                $borrow->setBookid($book);
                $borrow->setBorrowDate((new \DateTimeImmutable('now')));

                $check=$this->borrowService->borrowBook($borrow);
                if($check)
                {
                    return new JsonResponse([
                        "status"=>"Book Borrowed Successfully"
                    ], JsonResponse::HTTP_CREATED);
                }
            }
        }

        #[Route('/borrow/return/{id}', methods: ['POST'], name: 'return_book')]
        public function returnBook(int $id): JsonResponse
        {
            try{
            $borrow = $this->borrowRepository->find($id);
            if (!$borrow) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Borrow record not found'
                ], JsonResponse::HTTP_NOT_FOUND);
            }
         } catch (\Exception $e) {
            return new JsonResponse(["message"=>$e->getMessage()],JsonResponse::HTTP_NOT_FOUND);
         }

    
            $this->borrowService->returnBook($borrow);
            $getBookId=$this->bookService->getBookById($borrow->getBookid()->getId());
            $getBookId->setStatus(Status::from('available'));
            $changeStatus=$this->bookService->changeBookStatus($getBookId);
            
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Book returned successfully'
            ]);
        }    
}
