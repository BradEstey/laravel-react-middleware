<?php

namespace Estey\ReactMiddleware;

use Illuminate\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Closure;
use GuzzleHttp\Client;
use Estey\ReactMiddleware\Exceptions\InvalidArgumentException;
use Exception;

class CompileReact
{
    /**
     * Request Path.
     * @var string
     */
    protected $path;

    /**
     * Request Query String.
     * @var array
     */
    protected $query;

    /**
     * Response Object.
     * @var \Illuminate\Http\Response
     */
    protected $response;

    /**
     * View Object.
     * @var \Illuminate\View\View
     */
    protected $view;

    /**
     * Config Object.
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * New React Compiler Middleware.
     *
     * @param  \Illuminate\Config\Repository  $config
     * @return void
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $contentKey
     * @param  boolean  $isAjaxRespondWithJson
     * @return mixed
     */
    public function handle(
        Request $request,
        Closure $next,
        $contentKey = 'content',
        $ajaxRespondsWithJson = null
    ) {
        $this->path = $request->path();
        $this->query = $request->query();
        $this->response = $next($request);
        $this->view = $this->response->getOriginalContent();

        if (!is_a($this->view, 'Illuminate\View\View')) {
            throw new InvalidArgumentException(
                'React Compiler Middleware expects an ' .
                'instance of Illuminate\View\View.'
            );
        }

        if ($ajaxRespondsWithJson !== 'false' and $request->ajax()) {
            return $this->respondWithJson();
        }

        return $this->compile($contentKey);
    }

    /**
     * Make an HTTP request to the React compiler. Return the
     * compiled view string.
     *
     * @param  string  $contentKey
     * @return \Illuminate\Http\Response
     */
    protected function compile($contentKey)
    {
        $content = $this->getResponse();
        $contents = !is_object($content) ? [$contentKey => $content] : $content;

        foreach ($contents as $key => $value) {
            $this->view->with($key, $value);
        }

        return $this->response->setContent($this->view);
    }

    /**
     * Get the React compiler URL.
     *
     * @return string
     */
    protected function getCompilerUrl()
    {
        $host = $this->config->get('react.host', 'localhost');
        $port = $this->config->get('react.port', 3000);

        $path = $this->path;
        if ($path and substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }
        return $host . ':' . $port . $path;
    }

    /**
     * Make HTTP request to the React compiler URL.
     *
     * @param  \GuzzleHttp\Client  $client
     * @return string|object|null
     */
    protected function getResponse(Client $client = null)
    {
        $client = $client ?: new Client;

        try {
            $content = $client->request('POST', $this->getCompilerUrl(), [
                'query' => $this->query,
                'json' => $this->view->getData(),
                'connect_timeout' => $this->config->get('react.connect_timeout', 0),
                'timeout' => $this->config->get('react.timeout', 0)
            ])->getBody()
            ->getContents();

            if ($this->isJson($content)) {
                $content = json_decode($content);
            }
        } catch (Exception $e) {
            $content = null;
        }

        return $content;
    }

    /**
     * If request is AJAX, respond with JSON.
     *
     * @return \Illuminate\Http\Response
     */
    protected function respondWithJson()
    {
        return $this->response->setContent($this->view->getData());
    }

    /**
     * Is the given string JSON?
     *
     * @param  string  $string
     * @return boolean
     */
    protected function isJson($string)
    {
        json_decode($string);
        return json_last_error() == JSON_ERROR_NONE;
    }
}
