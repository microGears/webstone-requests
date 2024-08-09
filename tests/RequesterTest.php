<?php

namespace WebStone\Requests\Tests;

use PHPUnit\Framework\TestCase;
use WebStone\Requests\Requester;
use WebStone\Requests\Clients\ClientCurl;
use WebStone\Requests\Clients\Response;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use stdClass;
use WebStone\Requests\Clients\RequestClientAbstract;

class RequesterTest extends TestCase
{
    protected Requester $requester;
    protected RequestClientAbstract $client;

    protected function setUp(): void
    {
        $this->client = new ClientCurl([
            'timeout' => 10,
            'redirects' => 5,
            'blocking' => true,
            'protocol_version' => '1.1',
        ]);

        $this->requester = new Requester();        
        $this->requester->setPort(80);
        $this->requester->setProtocol('http');
        $this->requester->setHost('localhost');
        $this->requester->setClient($this->client);
    }

    public function testRequestWithGetMethod()
    {
        $response = $this->requester->request('/index.html', 'GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRequestWithPostMethod()
    {
        $headers = ['Content-Type' => 'application/json'];
        $content = ['key' => 'value'];

        $response = $this->requester->request('/index.html', 'POST', $headers, $content);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRequestWithAsync()
    {
        $response = $this->requester->request('/', 'GET', [], null, true);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRequestWithoutClient()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('failed retrieving class');
        $this->requester->setClient(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid client instance');
        $this->requester->setClient(new FakeClient());

        $this->requester->request('/', 'GET');
    }

    public function testRequestWithGetMethodAndWrongUrl()
    {
        $response = $this->requester->request('/index.php', 'GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }    
}

class FakeClient extends stdClass
{
}
