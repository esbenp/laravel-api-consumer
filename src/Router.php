<?php

namespace Optimus\ApiConsumer;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router as LaravelRouter;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Router {

    private $app;

    private $router;

    private $request;

    private $disableMiddleware = false;

    /**
     * @param \Illuminate\Foundation\Application $app
     * @param \Illuminate\Http\Request $request,
     * @param \Illuminate\Routing\Router $router
     */
    public function __construct(Application $app, Request $request, LaravelRouter $router)
    {
        $this->app = $app;
        $this->request = $request;
        $this->router = $router;
    }

    /**
     * @param  string $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  string $content
     * @return \Illuminate\Http\Response
     */
    public function get()
    {
        return $this->quickCall('GET', func_get_args());
    }

    /**
     * @param  string $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  string $content
     * @return \Illuminate\Http\Response
     */
    public function post()
    {
        return $this->quickCall('POST', func_get_args());
    }

    /**
     * @param  string $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  string $content
     * @return \Illuminate\Http\Response
     */
    public function put()
    {
        return $this->quickCall('PUT', func_get_args());
    }

    /**
     * @param  string $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  string $content
     * @return \Illuminate\Http\Response
     */
    public function delete()
    {
        return $this->quickCall('DELETE', func_get_args());
    }

    /**
     * @param  array $requests An array of requests
     * @return array
     */
    public function batchRequest(array $requests)
    {
        foreach($requests as $i => $request){
            $requests[$i] = call_user_func_array([$this, 'singleRequest'], $request);
        }

        return $requests;
    }

    /**
     * @param  string $method
     * @param  array  $args
     * @return \Illuminate\Http\Response
     */
    public function quickCall($method, array $args)
    {
        array_unshift($args, $method);
        return call_user_func_array([$this, "singleRequest"], $args);
    }

    /**
     * @param  string $method
     * @param  string $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  string $content
     * @return \Illuminate\Http\Response
     */
    public function singleRequest($method, $uri, array $data = [], array $headers = [], $content = null)
    {
        // Save the current request so we can reset the router back to it
        // after we've completed our internal request.
        $currentRequest = $this->request->instance()->duplicate();

        $headers = $this->overrideHeaders($currentRequest->server->getHeaders(), $headers);

        if ($this->disableMiddleware) {
            $this->app->instance('middleware.disable', true);
        }

        $response = $this->request($method, $uri, $data, $headers, $content);

        if ($this->disableMiddleware) {
            $this->app->instance('middleware.disable', false);
        }

        // Once the request has completed we reset the currentRequest of the router
        // to match the original request.
        $this->request->instance()->initialize(
            $currentRequest->query->all(),
            $currentRequest->request->all(),
            $currentRequest->attributes->all(),
            $currentRequest->cookies->all(),
            $currentRequest->files->all(),
            $currentRequest->server->all(),
            $currentRequest->content
        );

        return $response;
    }

    private function overrideHeaders(array $default, array $headers)
    {
        $headers = $this->transformHeadersToUppercaseUnderscoreType($headers);
        return array_merge($default, $headers);
    }

    public function enableMiddleware()
    {
        $this->disableMiddleware = false;
    }

    public function disableMiddleware()
    {
        $this->disableMiddleware = true;
    }

    /**
     * @param  string $method
     * @param  string $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  string $content
     * @return \Illuminate\Http\Response
     */
    private function request($method, $uri, array $data = [], array $headers = [], $content = null)
    {
        // Create a new request object for the internal request
        $request = $this->createRequest($method, $uri, $data, $headers, $content);

        // Handle the request in the kernel and prepare a response
        $response = $this->router->prepareResponse($request, $this->app->handle($request));

        return $response;
    }

    /**
     * @param  string $method
     * @param  string $uri
     * @param  array  $data
     * @param  array  $headers
     * @param  string $content
     * @return \Illuminate\Http\Request
     */
    private function createRequest($method, $uri, array $data = [], array $headers = [], $content = null)
    {
        $server = $this->transformHeadersToServerVariables($headers);

        return $this->request->create($uri, $method, $data, [], [], $server, $content);
    }

    private function transformHeadersToUppercaseUnderscoreType($headers)
    {
        $transformed = [];

        foreach($headers as $headerType => $headerValue) {
            $headerType = strtoupper(str_replace('-', '_', $headerType));

            $transformed[$headerType] = $headerValue;
        }

        return $transformed;
    }

    /**
     * https://github.com/symfony/symfony/issues/5074
     *
     * @param  array $headers
     * @return array
     */
    private function transformHeadersToServerVariables($headers)
    {
        $server = [];

        foreach($headers as $headerType => $headerValue){
            $headerType = 'HTTP_' . $headerType;

            $server[$headerType] = $headerValue;
        }

        return $server;
    }

}
