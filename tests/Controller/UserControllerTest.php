<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    // Test case to add a new user via API
    public function testAddNewUser()
    {
        $client = static::createClient();
        // Send a POST request to create a new user
        $client->request('POST', '/user', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'role' => 'Member',
            'password' => 'securepassword@123',
            'created_at'=>new \DateTimeImmutable('now'),
            'updated_at'=>new \DateTimeImmutable('now')
        ]));
        // dd($client->getResponse());
        // Assert the HTTP status code is 201 (Created)
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        // Assert the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test case to edit an existing user via API
    public function testEditUser()
    {
        $client = static::createClient();
        // Send a PUT request to update details of the user with ID 1
        $client->request('PUT', '/user/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Updated Test User',
            'email' => 'updatedtestuser@example.com',
            'role' => 'Admin',
            'password' => 'newsecurepassword',
            'created_at'=>new \DateTimeImmutable('now'),
            'updated_at'=>new \DateTimeImmutable('now')
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
        // Send a GET request to fetch details of a user with ID 1
        $client->request('GET', '/user/1/');

        // Assert the HTTP status code is 200 (OK)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // Assert the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test case to remove an existing user via API
    public function testRemoveUser()
    {
        $client = static::createClient();
        // Send a DELETE request to remove the user with ID 1
        $client->request('DELETE', 'user/delete/1');

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
        // dd($client->getResponse());
        // Assert that the response status code is 405
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        // Assert that the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test the return book functionality
    public function testReturnBook()
    {
        $client = static::createClient();
        
        // Send a POST request to the /borrows endpoint with the borrow ID
        $client->request('POST', '/borrow/return/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'returnDate' => '2023-07-10'
        ]));
        // Assert that the response status code is 200
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // Assert that the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }
}
?>
