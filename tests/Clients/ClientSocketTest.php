<?php

namespace Webstobe\Requests\Tests\Clients;

use PHPUnit\Framework\TestCase;
use WebStone\Requests\Clients\ClientSocket;
use WebStone\Requests\Clients\Response;

class ClientSocketTest extends TestCase
{
    protected $client;

    protected function setUp(): void
    {
        $this->client = new ClientSocket([
            'timeout' => 5,
            'redirects' => 5,
            'blocking' => true,
            'protocol_version' => '1.1',
        ]);
    }

    public function testIsSupported()
    {
        $this->assertTrue($this->client->isSupported());
    }

    public function testRequestWithGetMethod(){
        $response = $this->client->request('https://localhost','GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }


    public function testRequestWithPostMethod()
    {
        $url = 'https://localhost';
        $method = 'POST';
        $headers = ['Content-Type' => 'application/json'];
        $content = ['key' => 'value'];

        $response = $this->client->request($url, $method, $headers, $content);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
