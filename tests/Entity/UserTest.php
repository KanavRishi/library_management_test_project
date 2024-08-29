<?php
namespace App\Tests\Entity;

use App\Entity\User;
use App\ValueObject\Name;
use App\ValueObject\Email;
use App\Enum\Role;
use App\Enum\DeletionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Faker\Factory;

class UserTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::$kernel->getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Close the EntityManager after the test
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testUserPersistence(): void
    {
        $faker = Factory::create();

        // Create a User object
        $name = new Name("Kanav Arora");
        $email = new Email($faker->email());
        $password = 'password123';
        $user = new User($name, $email, $password);

        // Persist the User entity to the database
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Retrieve the User entity from the database
        $retrievedUser = $this->entityManager->getRepository(User::class)->findByEmail($email);
        // dd($retrievedUser);
        // Assert that the retrieved user matches the persisted user
        $this->assertSame($user, $retrievedUser);

        // Step 4: Update the User's email and persist the changes
        $newEmail = new Email($faker->email());
        $retrievedUser->setEmail($newEmail);
        $this->entityManager->flush();

        // Retrieve the updated user and verify the title was updated
        $updatedUser = $this->entityManager->getRepository(User::class)->findByEmail($newEmail);
        $this->assertEquals($newEmail, $updatedUser->getEmail());

        // Step 5: Delete the user
        $this->entityManager->remove($updatedUser);
        $this->entityManager->flush();

        // Verify that the user no longer exists in the database
        $deletedUser = $this->entityManager->getRepository(User::class)->findByEmail($email);
        $this->assertNull($deletedUser);
    }
}
