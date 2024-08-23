<?php
namespace App\Tests\Controller;
use Faker\Factory;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookControllerTest extends WebTestCase
{
    private $entityManager;
    // Test case to add a new book via API
    public function testAddNewBook()
    {
        $faker = Factory::create();
        $client = static::createClient();
        // Send a POST request to create a new book
        $client->request('POST', '/book', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => $faker->sentence(),
            'author' => $faker->name(),
            'isbn' => $faker->isbn13(),
            'publisheddate' => $faker->date(),
            'status' => 'available'
        ]));
        // dd($faker->date());
        // Assert the HTTP status code is 201 (Created)
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        // Assert the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test case to edit an existing book via API
    public function testEditBook()
    {
        $faker = Factory::create();
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $getId = $entityManager->getRepository(Book::class)->findOneBy([], ['id' => 'DESC']);
        // dd();

        // Send a PUT request to update details of the book with ID 1
        $client->request('PUT', '/book/' . $getId->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'title' => $faker->sentence(),
            'author' => $faker->name(),
            'isbn' => $faker->isbn13(),
            'publisheddate' => $faker->date(),
            'status' => 'available'
        ]));

        // Assert the HTTP status code is 200 (OK)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // Assert the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test case to view the list of books via API
    public function testViewListOfBooks()
    {
        $client = static::createClient();
        // Send a GET request to fetch the list of books
        $client->request('GET', '/book');
        // dd($client->getResponse());
        // Assert the HTTP status code is 200 (OK)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // Assert the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test case to view details of a specific book via API
    public function testViewBookDetails()
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $getId = $entityManager->getRepository(Book::class)->findOneBy([], ['id' => 'DESC']);
        $id = $getId->getId();
        // Send a GET request to fetch details of a book with ID 1
        $client->request('GET', '/book/' . $getId->getId() . '/');
        // dd($client->getResponse());
        // Assert the HTTP status code is 200 (OK)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // Assert the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }

    // Test case to remove an existing book via API
    public function testRemoveBook()
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $getId = $entityManager->getRepository(Book::class)->findOneBy([], ['id' => 'DESC']);
        $id = $getId->getId();
        // Send a DELETE request to remove the book with ID 1
        $client->request('DELETE', '/book/delete/' . $id);
        // Assert the HTTP status code is 200
        // dd($client->getResponse());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
?>