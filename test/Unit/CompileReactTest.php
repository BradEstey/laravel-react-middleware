<?php

namespace Estey\ReactMiddleware\Test\Unit;

use Estey\ReactMiddleware\CompileReact;
use Estey\ReactMiddleware\Exceptions\InvalidArgumentException;
use Mockery as m;

class CompileReactTest extends TestCase
{
    /**
     * New React Compiler Middleware Test.
     *
     * @return void
     */
    public function __construct()
    {
        $this->config = m::mock('Illuminate\Config\Repository');
        $this->response = m::mock('Illuminate\Http\Response');
        $this->view = m::mock('Illuminate\View\View');
    }

    /**
     * Test handle() method.
     *
     * @return void
     */
    public function testHandle()
    {
        $mock = m::mock(
            'Estey\ReactMiddleware\CompileReact[respondWithJson, compile]',
            [$this->config]
        )->shouldAllowMockingProtectedMethods();

        $request = m::mock('Illuminate\Http\Request');
        $next = function ($request) {
            return $this->response;
        };

        $request
            ->shouldReceive('path')
            ->once();

        $this->response
            ->shouldReceive('getOriginalContent')
            ->once()
            ->andReturn($this->view);

        $request
            ->shouldReceive('ajax')
            ->once()
            ->andReturn(false);

        $mock
            ->shouldReceive('compile')
            ->once()
            ->with('content')
            ->andReturn('foo bar baz');

        $this->assertEquals(
            $mock->handle($request, $next),
            'foo bar baz'
        );
    }

    /**
     * Test handle() method. Don't respond with JSON.
     *
     * @return void
     */
    public function testHandleNoAjax()
    {
        $mock = m::mock(
            'Estey\ReactMiddleware\CompileReact[respondWithJson, compile]',
            [$this->config]
        )->shouldAllowMockingProtectedMethods();

        $request = m::mock('Illuminate\Http\Request');
        $next = function ($request) {
            return $this->response;
        };

        $request
            ->shouldReceive('path')
            ->once();

        $this->response
            ->shouldReceive('getOriginalContent')
            ->once()
            ->andReturn($this->view);

        $mock
            ->shouldReceive('compile')
            ->once()
            ->with('foo')
            ->andReturn('foo bar baz');

        $this->assertEquals(
            $mock->handle($request, $next, 'foo', false),
            'foo bar baz'
        );
    }

    /**
     * Test handle() method. Is AJAX, respond with JSON.
     *
     * @return void
     */
    public function testHandleAjax()
    {
        $mock = m::mock(
            'Estey\ReactMiddleware\CompileReact[respondWithJson, compile]',
            [$this->config]
        )->shouldAllowMockingProtectedMethods();

        $request = m::mock('Illuminate\Http\Request');
        $next = function ($request) {
            return $this->response;
        };

        $request
            ->shouldReceive('path')
            ->once();

        $this->response
            ->shouldReceive('getOriginalContent')
            ->once()
            ->andReturn($this->view);

        $request
            ->shouldReceive('ajax')
            ->once()
            ->andReturn(true);

        $mock
            ->shouldReceive('respondWithJson')
            ->once()
            ->andReturn('{"foo": "bar"}');

        $this->assertEquals(
            $mock->handle($request, $next),
            '{"foo": "bar"}'
        );
    }

    /**
     * Test handle() method. Throws an exception.
     *
     * @expectedException  InvalidArgumentException
     * @expectedExceptionMessage  React Compiler Middleware expects an instance of Illuminate\View\View.
     */
    public function testHandleException()
    {
        $mock = m::mock(
            'Estey\ReactMiddleware\CompileReact[respondWithJson, compile]',
            [$this->config]
        )->shouldAllowMockingProtectedMethods();

        $request = m::mock('Illuminate\Http\Request');
        $next = function ($request) {
            return $this->response;
        };

        $request
            ->shouldReceive('path')
            ->once();

        $this->response
            ->shouldReceive('getOriginalContent')
            ->once()
            ->andReturn(null);


        $mock->handle($request, $next);
    }

    /**
     * Test compile() method.
     *
     * @return void
     */
    public function testCompile()
    {
        $mock = m::mock(
            'Estey\ReactMiddleware\CompileReact[getResponse]',
            [$this->config]
        )->shouldAllowMockingProtectedMethods();

        $this->setInaccessible($mock, 'response', $this->response);
        $this->setInaccessible($mock, 'view', $this->view);

        $mock->shouldReceive('getResponse')->once()->andReturn('foo bar');

        $this->view
            ->shouldReceive('with')
            ->once()
            ->with('foo', 'foo bar')
            ->andReturn($this->view);

        $this->response
            ->shouldReceive('setContent')
            ->once()
            ->with($this->view)
            ->andReturn('foo bar baz');

        $this->assertEquals(
            $this->callInaccessibleMethod($mock, 'compile', ['foo']),
            'foo bar baz'
        );
    }

    /**
     * Test getCompilerUrl() method.
     *
     * @return void
     */
    public function testGetCompilerUrl()
    {
        $stub = new CompileReact($this->config);

        $this->config
            ->shouldReceive('get')
            ->once()
            ->with('react.host', 'localhost')
            ->andReturn('localhost');

        $this->config
            ->shouldReceive('get')
            ->once()
            ->with('react.port', 3000)
            ->andReturn(3000);

        $this->setInaccessible($stub, 'path', '/');

        $this->assertEquals(
            $this->callInaccessibleMethod($stub, 'getCompilerUrl'),
            'localhost:3000/'
        );
    }

    /**
     * Test getResponse() method.
     *
     * @return void
     */
    public function testGetResponse()
    {
        $mock = m::mock(
            'Estey\ReactMiddleware\CompileReact[getCompilerUrl]',
            [$this->config]
        )->shouldAllowMockingProtectedMethods();

        $this->setInaccessible($mock, 'view', $this->view);

        $mock->shouldReceive('getCompilerUrl')->once()->andReturn('localhost');

        $client = m::mock('GuzzleHttp\Client');
        $response = m::mock('Psr\Http\Message\ResponseInterface');

        $this->view
            ->shouldReceive('getData')
            ->once()
            ->andReturn(['foo' => 'bar']);

        $this->config
            ->shouldReceive('get')
            ->once()
            ->with('react.connect_timeout', 0)
            ->andReturn(1);

        $this->config
            ->shouldReceive('get')
            ->once()
            ->with('react.timeout', 0)
            ->andReturn(15);

        $options = [
            'json' => ['foo' => 'bar'],
            'connect_timeout' => 1,
            'timeout' => 15
        ];

        $client
            ->shouldReceive('request')
            ->once()
            ->with('POST', 'localhost', $options)
            ->andReturn($response);

        $response
            ->shouldReceive('getBody')
            ->once()
            ->andReturn('foo bar baz');

        $this->assertEquals(
            $this->callInaccessibleMethod($mock, 'getResponse', [$client]),
            'foo bar baz'
        );
    }

    /**
     * Test getResponse() method throws exception.
     *
     * @return void
     */
    public function testGetResponseWithException()
    {
        $mock = m::mock(
            'Estey\ReactMiddleware\CompileReact[getCompilerUrl]',
            [$this->config]
        )->shouldAllowMockingProtectedMethods();

        $this->setInaccessible($mock, 'view', $this->view);

        $mock->shouldReceive('getCompilerUrl')->once()->andReturn('localhost');

        $client = m::mock('GuzzleHttp\Client');
        $response = m::mock('Psr\Http\Message\ResponseInterface');

        $this->view
            ->shouldReceive('getData')
            ->once()
            ->andReturn(['foo' => 'bar']);

        $this->config
            ->shouldReceive('get')
            ->once()
            ->with('react.connect_timeout', 0)
            ->andReturn(1);

        $this->config
            ->shouldReceive('get')
            ->once()
            ->with('react.timeout', 0)
            ->andReturn(15);

        $options = [
            'json' => ['foo' => 'bar'],
            'connect_timeout' => 1,
            'timeout' => 15
        ];

        $client
            ->shouldReceive('request')
            ->once()
            ->with('POST', 'localhost', $options)
            ->andThrow(m::mock('Exception'));

        $this->assertNull(
            $this->callInaccessibleMethod($mock, 'getResponse', [$client])
        );
    }

    /**
     * Test respondWithJson() method.
     *
     * @return void
     */
    public function testRespondWithJson()
    {
        $stub = new CompileReact($this->config);

        $this->setInaccessible($stub, 'response', $this->response);
        $this->setInaccessible($stub, 'view', $this->view);

        $this->view
            ->shouldReceive('getData')
            ->once()
            ->andReturn(['foo' => 'bar']);

        $this->response
            ->shouldReceive('setContent')
            ->once()
            ->with(['foo' => 'bar'])
            ->andReturn('{ "foo": "bar" }');

        $this->assertEquals(
            $this->callInaccessibleMethod($stub, 'respondWithJson'),
            '{ "foo": "bar" }'
        );
    }
}
