<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BorrowControllerTest extends WebTestCase
{
    

    // Test the view borrowing history functionality
    public function testViewBorrowingHistory()
    {
        $client = static::createClient();
        // Send a GET request to the /history endpoint
        $client->request('GET', '/borrow');

        // Assert that the response status code is 200 (OK)
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // Assert that the response content is JSON
        $this->assertJson($client->getResponse()->getContent());
    }
}
?>