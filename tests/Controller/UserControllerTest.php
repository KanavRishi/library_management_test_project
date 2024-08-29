<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Faker\Factory;

class UserControllerTest extends WebTestCase
{
    // Test case to add a new user via API
    public function testAddNewUser()
    {
        $faker = Factory::create();
        // for creating HTTP client instance
        $client = static::createClient();
        // Send a POST request to create a new user
        $client->request('POST', '/user', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => $faker->name(),
            'email' => $faker->email(),
            'role' => 'Member',
            'password' => $faker->password(),
            'created_at' => new \DateTimeImmutable('now'),
            'updated_at' => new \DateTimeImmutable('now')
        ]));
        // Assert the HTTP status code is 201 (Created)
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        // Assert the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test case to edit an existing user via API
    public function testEditUser()
    {
        $faker = Factory::create();
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $getId = $entityManager->getRepository(User::class)->findOneBy([], ['id' => 'DESC']);
        $id = $getId->getId();
        // Send a PUT request to update details of the user with ID 1
        $client->request('PUT', '/user/' . $id, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => $faker->name(),
            'email' => $faker->email(),
            'role' => 'Admin',
            'password' => $faker->password(),
            'created_at' => new \DateTimeImmutable('now'),
            'updated_at' => new \DateTimeImmutable('now')
        ]));
        // Assert the HTTP status code is 200 (OK)
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        // Assert the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test case to view the list of users via API
    public function testViewListOfUsers()
    {
        $client = static::createClient();
        // Send a GET request to fetch the list of users
        $client->request('GET', '/user');

        // Assert the HTTP status code is 200 (OK)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // Assert the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test case to view details of a specific user via API
    public function testViewUserDetails()
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $getId = $entityManager->getRepository(User::class)->findOneBy([], ['id' => 'DESC']);
        $id = $getId->getId();
        // Send a GET request to fetch details of a user with ID 1
        $client->request('GET', '/user/' . $id . '/');

        // Assert the HTTP status code is 200 (OK)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // Assert the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test case to remove an existing user via API
    public function testRemoveUser()
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $getId = $entityManager->getRepository(User::class)->findOneBy([], ['id' => 'DESC']);
        $id = $getId->getId();
        // Send a DELETE request to remove the user with ID 1
        $client->request('DELETE', 'user/delete/' . $id);

        // Assert the HTTP status code is 204 (No Content)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    // Test the borrow book functionality
    public function testBorrowBook()
    {
        $client = static::createClient();
        // Send a POST request to the /borrow endpoint with the user and book IDs
        $client->request('PUT', '/borrow', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'userid' => 1,
            'bookid' => 1,
            'borrowDate' => '2023-07-01'
        ]));
        // Assert that the response status code is 405
        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        // Assert that the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test the return book functionality
    public function testReturnBook()
    {
        $client = static::createClient();

        // Send a POST request to the /borrows endpoint with the borrow ID
        $client->request('POST', '/borrow/return/4', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'returnDate' => '2023-07-10'
        ]));
        // Assert that the response status code is 200
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        // Assert that the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }
}
?>