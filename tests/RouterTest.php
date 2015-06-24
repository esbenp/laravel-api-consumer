<?php

use Mockery as m;

class RouterTest extends Orchestra\Testbench\TestCase {

    const classPath = "Optimus\ApiConsumer\Router";

    private $appMock;

    private $requestMock;

    private $routerMock;

    public function setUp()
    {
        parent::setUp();

        $this->appMock = m::mock("Illuminate\Foundation\Application");
        $this->requestMock = m::mock("Illuminate\Http\Request");
        $this->routerMock = m::mock("Illuminate\Routing\Router");
    }

    public function tearDown()
    {
        parent::tearDown();

        m::close();
    }

    public function testThatQuickCallCorrectlyCallsUnderlyingMethod()
    {
        $mock = m::mock(self::classPath . '[singleRequest]', [
            $this->appMock,
            $this->requestMock,
            $this->routerMock
        ]);

        $mock->shouldReceive('singleRequest')->times(4)->withArgs([
            m::anyOf('GET', 'POST', 'PUT', 'DELETE'),
            '/endpoint',
            ['data']
        ]);

        $mock->get('/endpoint', ['data']);
        $mock->post('/endpoint', ['data']);
        $mock->put('/endpoint', ['data']);
        $mock->delete('/endpoint', ['data']);
    }

    public function testThatBatchRequestWillCallSingleRequest()
    {
        $mock = m::mock(self::classPath . '[singleRequest]', [
            $this->appMock,
            $this->requestMock,
            $this->routerMock
        ]);

        $mock->shouldReceive('singleRequest')->times(2)->withArgs([
            m::anyOf('GET', 'POST'), '/endpoint', ['data']
        ]);

        $mock->batchRequest([
            ['GET', '/endpoint', ['data']],
            ['POST', '/endpoint', ['data']]
        ]);
    }

    public function testThatQuickCallCorrectlyCallsSingleRequest()
    {
        $mock = m::mock(self::classPath . '[singleRequest]', [
            $this->appMock,
            $this->requestMock,
            $this->routerMock
        ]);

        $mock->shouldReceive('singleRequest')->times(2)->withArgs([
            m::anyOf('GET', 'POST'),
            '/endpoint',
            ['data'],
            ['server'],
            'content'
        ]);

        $mock->quickCall('GET', [
            '/endpoint',
            ['data'],
            ['server'],
            'content'
        ]);

        $mock->quickCall('GET', [
            '/endpoint',
            ['data'],
            ['server'],
            'content'
        ]);
    }

    public function testThatRequestIsMadeCorrectlyAndThatHeadersAreCorrectlySet()
    {
        $routerMock = m::mock('Illuminate\Routing\Router');

        $routerMock->shouldReceive('prepareResponse')->times(1)->with(
            m::on(function($request){
                $this->assertEquals("content", $request->getContent());
                $this->assertEquals(['data'], $request->query->all());
                $this->assertEquals('/endpoint?0=data', $request->server->get('REQUEST_URI'));
                $this->assertTrue(array_key_exists('x-requested-with', $request->headers->all()));
                return true;
            }),
            m::any()
        );

        $appMock = m::mock('Illuminate\Foundation\Application');

        $appMock->shouldReceive('handle')->times(1)->with(
            m::type('Illuminate\Http\Request')
        );

        $class = self::classPath;
        $router = new $class($appMock, $this->app['request'], $routerMock);

        $router->get('/endpoint', ['data'], [
            'X-Requested-With' => 'XMLHttpRequest'
        ], "content");


    }
    
}