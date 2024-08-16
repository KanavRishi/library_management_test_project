<?php
namespace App\Tests\Entity;

use App\Entity\User;
use App\ValueObject\Name;
use App\ValueObject\Email;
use App\Enum\Role;
use App\Enum\DeletionStatus;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserEntity()
    {
        // Create instances of Value Objects and Enums for testing
        $name = new Name("Kanav Rishi");
        $email = new Email("kanav.rishi@example.com");
        $password = "Kanav@123!";
        $role = Role::MEMBER;
        $deletionStatus = DeletionStatus::ACTIVE;
        $createdAt = new \DateTimeImmutable();
        $updatedAt = new \DateTimeImmutable();

        // Instantiate the User entity
        $user = new User($name, $email, $password);

        // Test constructor values
        $this->assertSame($name, $user->getName());
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($password, $user->getPassword());
        $this->assertSame($role, $user->getRole());
        $this->assertSame($deletionStatus, $user->getDeletionStatus());

        // Test setting and getting the Role
        $newRole = Role::ADMIN;
        $user->setRole($newRole);
        $this->assertSame($newRole, $user->getRole());

        // Test setting and getting the Password
        $newPassword = "NewSecurePass456!";
        $user->setPassword($newPassword);
        $this->assertSame($newPassword, $user->getPassword());

        // Test setting and getting the created_at date
        $user->setCreatedAt($createdAt);
        $this->assertSame($createdAt, $user->getCreatedAt());

        // Test setting and getting the updated_at date
        $user->setUpdatedAt($updatedAt);
        $this->assertSame($updatedAt, $user->getUpdatedAt());

        // Test setting and getting the DeletionStatus
        $newDeletionStatus = DeletionStatus::DELETED;
        $user->setDeletionStatus($newDeletionStatus);
        $this->assertSame($newDeletionStatus, $user->getDeletionStatus());

        // Test that the timestamps are updated correctly with lifecycle callbacks
        $user->updatedTimestamps();
        $this->assertNotNull($user->getUpdatedAt());

        if ($user->getCreatedAt() === null) {
            $this->assertNotNull($user->getCreatedAt());
        }
    }
}
