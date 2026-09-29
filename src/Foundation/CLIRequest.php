<?php

namespace Pano\Foundation;

use Pano\Kernel\BaseRequest;
use Pano\Kernel\HttpMethodEnum;

class CliRequest extends BaseRequest
{

    public function __construct(array $data)
    {
        $this->fetchMethod()
            ->fetchSegments($data)
            ->setModule($data)
            ->fetchOptions($data)
            ->fetchPath($data)
            ->fetchPositional($data)
            ->fetchCommand()
            ->fetchFiles()
            ->fetchQueries();
    }

    public function getPositional(): string|array
    {
        return $this->data;
    }

    public function getOptions(): array
    {
        return $this->headers;
    }

    public function getCommand(): string
    {
        return $this->url;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getPath(): string
    {
        return $this->host;
    }

    public function expectsJson(): bool
    {
        return false;
    }

    private function fetchMethod(): self
    {
        $this->method = HttpMethodEnum::CLI;
        return $this;
    }

    /**
     * option arguments
     */
    private function fetchOptions(array $data): self
    {
        $parameters = [];
        foreach ($data as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    $parameters[$key] = $value;
                } else {
                    $key = trim(substr($arg, 2));
                    $parameters[$key] = true;
                }
            }
        }
        $this->headers = $parameters;
        return $this;
    }

    private function fetchPath(array $data): self
    {
        $this->host = trim($data[1]) === '/' ? '' : trim($data[1]);
        return $this;
    }

    private function fetchSegments(array $data): self
    {
        $this->segments = explode('/', $data[2]);
        return $this;
    }

    private function fetchCommand(): self
    {
        $url = '/'  . $this->segments[0];
        foreach ($this->data as $item) {
            $url .= ' ' . $item;
        }
        foreach ($this->headers as $key => $item) {
            $url .= " --$key=" . $item;
        }
        $this->url = str_replace(' ', '/', $url);
        return $this;
    }

    /**
     * positional arguments
     */
    private function fetchPositional(array $data): self
    {
        $this->data = [];
        $arguments = array_slice($data, 3);
        foreach ($arguments as $argument) {
            if (!str_starts_with($argument, '--')) {
                $this->data[] = $argument;
            }
        }
        return $this;
    }

    private function fetchFiles(): self
    {
        $this->files = [];
        return $this;
    }

    private function fetchQueries(): void
    {
        $this->queries = [];
    }

    private function setModule(array $data): self
    {
        $this->module = $data[1] === '/' ? '' : $data[1];
        return $this;
    }

}