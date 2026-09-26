<?php

namespace Pano\Kernel;

use Exception;
use ReflectionClass;
use ReflectionNamedType;

abstract class BaseRouter
{

    abstract public function get(string $path, string $class, string $action, array $interceptors = []): void;

    abstract public function post(string $path, string $class, string $action, array $interceptors = []): void;

    abstract public function put(string $path, string $class, string $action, array $interceptors = []): void;

    abstract public function delete(string $path, string $class, string $action, array $interceptors = []): void;

    abstract protected function notFound(): mixed;

    private array $routes = [];
    private array $commands = [];
    private array $groupPrefixes = [];
    private array $groupInterceptors = [];

    public function __construct(
        protected BaseRequest $request,
        protected BaseModule  $module,
        protected array       $interceptors = []
    )
    {
    }

    public function group(string $prefix, callable $callback, array $interceptors = []): void
    {
        $prefix = $this->normalizePath($prefix);
        if ($prefix === '/') {
            $prefix = '';
        }

        $this->groupPrefixes[] = $prefix;
        $this->groupInterceptors[] = $interceptors;

        try {
            $callback($this);
        } finally {
            array_pop($this->groupPrefixes);
            array_pop($this->groupInterceptors);
        }
    }

    /**
     * @throws Exception
     */
    public function command(string $path, string $class): void
    {
        if (!class_exists($class)) {
            throw new Exception("Command ($class) not found");
        }

        $reflection = new ReflectionClass($class);
        if (!$reflection->isSubclassOf(BaseCommand::class)) {
            throw new Exception("Command ($class) must extend " . BaseCommand::class);
        }

        $path = $this->normalizePath($path);
        [$pattern, $params, $options] = $this->compile($path);

        $this->commands[] = [
            'command' => explode('/', $path)[0],
            'pattern' => $pattern,
            'params' => $params,
            'options' => $options,
            'handler' => $class,
        ];
    }

    public function handle(): mixed
    {
        return ($this->request->getMethod() === HttpMethodEnum::CLI)
            ? $this->dispatchConsole()
            : $this->dispatchHttp();
    }

    protected function register(
        HttpMethodEnum $method,
        string         $path,
        string         $class,
        string         $action,
        array          $interceptors = []
    ): void {
        if (!class_exists($class)) {
            throw new Exception("Handler ($class) not found");
        }

        $this->checkHandler($class, $action);

        $fullPath = $path;
        if ($this->groupPrefixes !== []) {
            $prefix = implode('', $this->groupPrefixes);
            $normalizedPath = $this->normalizePath($path);
            $fullPath = $prefix === ''
                ? $normalizedPath
                : rtrim($prefix, '/') . ($normalizedPath === '/' ? '' : $normalizedPath);
        }

        $fullPath = $this->normalizePath($fullPath);

        [$pattern, $params, $options] = $this->compile($fullPath);

        $mergedInterceptors = $this->interceptors;
        foreach ($this->groupInterceptors as $groupList) {
            foreach ($groupList as $interceptor) {
                $mergedInterceptors[] = $interceptor;
            }
        }
        foreach ($interceptors as $interceptor) {
            $mergedInterceptors[] = $interceptor;
        }

        foreach ($mergedInterceptors as $interceptor) {
            $this->checkInterceptor($interceptor);
        }

        $this->routes[$method->value][] = [
            'pattern'      => $pattern,
            'params'       => $params,
            'options'      => $options,
            'handler'      => [$class, $action],
            'interceptors' => $mergedInterceptors,
        ];
    }

    protected function compile(string $path): array
    {
        $params = [];
        $options = [];

        $path = $path === '/' ? '/' : rtrim($path, '/');

        if ($path !== '/') {
            $segments = explode('/', trim($path, '/'));
            $lastIndex = count($segments) - 1;

            foreach ($segments as $index => $segment) {

                if (
                    preg_match(
                        '/^\[[a-zA-Z_][a-zA-Z0-9_]*([\?\*])\]$/',
                        $segment
                    )
                    && $index !== $lastIndex
                ) {
                    throw new Exception(
                        "Optional [?] and catch-all [*] parameters must be the last route segment"
                    );
                }
            }
        }

        $pattern = preg_replace_callback(
            '/\/?\[([a-zA-Z_][a-zA-Z0-9_]*)([\?\*])?\]/',
            function ($matches) use (&$params, &$options) {

                $name = $matches[1];
                $flag = $matches[2] ?? null;

                $params[] = $name;

                return match ($flag) {

                    '*' => '(?:/(?P<' . $name . '>.+))?',

                    '?' => (function () use ($name, &$options) {
                        $options[] = $name;
                        return '(?:/(?P<' . $name . '>[^/]+))?';
                    })(),

                    default => '/(?P<' . $name . '>[^/]+)',
                };
            },
            $path
        );

        return [
            '#^' . $pattern . '$#',
            $params,
            $options,
        ];
    }

    private function normalizeUri(string $uri): string
    {
        return $this->normalizePath(
            parse_url($uri, PHP_URL_PATH) ?? '/'
        );
    }

    private function checkHandler(string $class, string $action): void
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isSubclassOf(BaseHandler::class)) {
            throw new Exception("Handler ($class) must extend " . BaseHandler::class);
        }

        if (!$reflection->hasMethod($action)) {
            throw new Exception("Action method $action is not defined in handler $class");
        }

        $method = $reflection->getMethod($action);

        if (!$method->isPublic()) {
            throw new Exception("Action method $action exists in handler $class but is not public");
        }

        $returnType = $method->getReturnType();

        if (!$returnType) {
            throw new Exception(
                "Action method $action in handler $class must declare a return type"
            );
        }

        if ($returnType instanceof ReflectionNamedType) {

            if ($returnType->isBuiltin()) {
                throw new Exception(
                    "Action method $action in handler $class must return BaseResponse"
                );
            }

            if ($returnType->getName() !== BaseResponse::class
                && !is_subclass_of($returnType->getName(), BaseResponse::class)
            ) {
                throw new Exception(
                    "Action method $action in handler $class must return BaseResponse"
                );
            }
        }

    }

    private function checkInterceptor(string $class): void
    {
        if (!class_exists($class)) {
            throw new Exception(
                "Interceptor ($class) not found"
            );
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            throw new Exception(
                "Interceptor ($class) cannot be abstract"
            );
        }

        if (!$reflection->isSubclassOf(BaseInterceptor::class)) {
            throw new Exception(
                "Interceptor ($class) must extend "
                . BaseInterceptor::class
            );
        }
    }

    /**
     * @throws Exception
     */
    private function dispatchConsole(): mixed
    {
        $options = $this->request->getHeaders();
        $positional = $this->request->getData();
        foreach ($this->commands as $command) {

            if (preg_match($command['pattern'], $this->request->getUrl()) !== 1) {
                continue;
            }

            $params = [];

            foreach ($command['params'] as $name) {
                $params[$name] = null;
            }

            foreach ($options as $key => $value) {
                $params[$key] = $value;
            }

            foreach ($command['params'] as $key => $name) {

                if (isset($positional[$key])) {
                    $params[$name] = $positional[$key];
                    continue;
                }

                if (!in_array($name, $command['options'], true)) {
                    throw new Exception(
                        "Parameter '$name' is required"
                    );
                }
            }

            return (new $command['handler'](
                $this->request,
                $this->module
            ))->handle($params);
        }

        return $this->notFound();
    }

    private function dispatchHttp(): mixed
    {
        $method = $this->request->getMethod();
        $uri = $this->normalizeUri($this->request->getUrl());

        $routes = $this->routes[$method->value] ?? null;

        if ($routes === null) {
            return $this->notFound();
        }

        foreach ($routes as $route) {

            if (preg_match($route['pattern'], $uri, $matches) !== 1) {
                continue;
            }

            $args = [];

            foreach ($route['params'] as $name) {
                $args[] = $matches[$name] ?? null;
            }

            $interceptors = array_map(
                fn(string $class) => new $class($this->request),
                $route['interceptors']
            );

            foreach ($interceptors as $interceptor) {
                $interceptor->onRequest();
                $this->request = $interceptor->request;
            }

            [$handlerClass, $action] = $route['handler'];

            $handler = new $handlerClass(
                $this->request,
                $this->module
            );

            $response = $handler->$action(...$args);

            if (!$response instanceof BaseResponse) {
                throw new Exception(
                    sprintf(
                        'Handler %s::%s() must return %s',
                        $handlerClass,
                        $action,
                        BaseResponse::class
                    )
                );
            }

            foreach (array_reverse($interceptors) as $interceptor) {
                $response = $interceptor->onResponse($response);
            }

            return $response->send();
        }

        return $this->notFound();
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/' . trim($path, '/');
    }
}
