<?php

namespace Pano\Foundation;

use Pano\Kernel\BaseRouter;
use Pano\Kernel\HttpMethodEnum;
use Pano\Kernel\HttpStatusEnum;

final class Router extends BaseRouter
{

    /**
     * @param string $path
     * @param string $class
     * @param string $action
     * @param array $interceptors
     * @throws Exception
     */
    public function get(string $path, string $class, string $action, array $interceptors = []): void
    {
        try {
            $this->register(HttpMethodEnum::GET, $path, $class, $action, $interceptors);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), HttpStatusEnum::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param string $path
     * @param string $class
     * @param string $action
     * @param array $interceptors
     * @throws Exception
     */
    public function post(string $path, string $class, string $action, array $interceptors = []): void
    {
        try {
            $this->register(HttpMethodEnum::POST, $path, $class, $action, $interceptors);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), HttpStatusEnum::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param string $path
     * @param string $class
     * @param string $action
     * @param array $interceptors
     * @throws Exception
     */
    public function put(string $path, string $class, string $action, array $interceptors = []): void
    {
        try {
            $this->register(HttpMethodEnum::PUT, $path, $class, $action, $interceptors);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), HttpStatusEnum::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param string $path
     * @param string $class
     * @param string $action
     * @param array $interceptors
     * @throws Exception
     */
    public function delete(string $path, string $class, string $action, array $interceptors = []): void
    {
        try {
            $this->register(HttpMethodEnum::DELETE, $path, $class, $action, $interceptors);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), HttpStatusEnum::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws Exception
     */
    public function command(string $path, string $class): void
    {
        try {
            $this->register(HttpMethodEnum::CLI, $path, $class, 'handle');
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), HttpStatusEnum::INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws Exception
     */
    protected function notFound(): int
    {
        if (HttpMethodEnum::CLI === $this->request->getMethod()) {
            throw new Exception('Command not found', 404, HttpStatusEnum::NOT_FOUND);
        }

        throw new Exception('Page not found', 404, HttpStatusEnum::NOT_FOUND);
    }

}