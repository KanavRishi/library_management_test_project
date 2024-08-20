<?php
// src/Service/UserService.php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\Role;
use App\ValueObject\Name;
use App\ValueObject\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

class UserService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager,ValidatorInterface $validator, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->validator=$validator;
    }

    public function createUser(string $name,string $email,string $password,$role): User
    {
        // dd($user);
        $user = new User(new Name($name),new Email($email),$password,$role);
        
        return $user;
        
    }

    public function saveUser(User $user): void
    {
        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new ValidatorException(implode(', ', $errors));
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function updateUser($id,$data): User
    {
        $user = $this->getUserById($id);
        if(!$user)
        {
            throw new \Exception("User not found");
        }
        $user->setName(new Name($data['name']));
        $user->setEmail(new Email($data['email']));
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setRole(Role::from($data['role']));

        return $user;
    }
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findOneBy(['id'=>$id,'deletionStatus'=>'active']);
    }
    public function listUsers(): array
    {
        return $this->userRepository->findAll();
    }
    public function deleteUser(int $id): bool
    {
        $user=$this->userRepository->find($id);

        if(!$user)
        {
            return false;
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        return true;
    }
    public function checkDuplUser(string $email): bool
    {
        $check_dupl= $this->userRepository->findOneBy(['email.value'=>$email]);
        if($check_dupl)
        {
            return true;
        }
        return false;
    }
}


?>