<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\ValueObject\Name;
use App\ValueObject\Email;
use App\Enum\Role;
use App\Enum\DeletionStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class UserTest extends TestCase
{
    public function testStoreUserInCache(): void
    {
        // Create a mock for the CacheInterface
        $cache = $this->createMock(CacheInterface::class);

        $name = new Name("Kanav");
        $email = new Email("kanav@gmail.com");
        $password = "Secure@123";
        $user = new User($name, $email, $password);

        // $user->setValue($user,123);

        $cacheKey = 'user_' . $user->getId();

        // Create a mock for the User object
        $user = $this->createMock(User::class);

        // Configure the mock to return a specific ID

        // Configure the cache mock to expect the 'get' method call
        $cache->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo($cacheKey),
                $this->callback(function ($callback) use ($user) {
                    // Simulate the cache storing the user if it's not found
                    return $callback() === $user;
                })
            )
            ->willReturn($user);
        // Use the cache's get method to either retrieve or store the user
        $retrievedUser = $cache->get($cacheKey, function () use ($user) {
            return $user;
        });

        // Assert that the retrieved user is the same as the stored user
        $this->assertSame($user, $retrievedUser);

    }
}
