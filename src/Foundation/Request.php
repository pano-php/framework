<?php

namespace Pano\Foundation;

use Pano\Kernel\BaseRequest;
use Pano\Kernel\HttpMethodEnum;

class Request extends BaseRequest
{
    public function __construct(array $data, null|string $module = null)
    {
        $this->fetchMethod($data)
            ->fetchSegments($data)
            ->setModule($module)
            ->fetchQuery($data)
            ->fetchHost($data)
            ->fetchUrl()
            ->fetchData()
            ->fetchFiles()
            ->fetchHeaders();

        /** @var Bag $attributes */
        $this->attributes = new Bag();
    }

    protected function setModule(null|string $module = null): static
    {
        if ($module !== null) {
            $this->module = $module;
        } else if (config('app.resolver') === 'subdomain') {
            $host = parse_url($this->host, PHP_URL_HOST);
            $rootDomain = parse_url(config('app.url'), PHP_URL_HOST);

            if ((empty($host) || empty($rootDomain))
                || (($host === $rootDomain)
                    || !str_ends_with($host, '.' . $rootDomain))) {
                $this->module = '';
            } else {
                $this->module = rtrim(
                    substr($host, 0, -strlen($rootDomain)),
                    '.'
                );
            }
        } else {
            $this->module = $this->segments[0] ?? '';
        }

        return $this;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function expectsJson(): bool
    {
        $accept = $this->headers['accept'] ?? '';
        return str_contains($accept, '*/json');
    }

    private function fetchData(): self
    {
        if (!empty($_POST)) {
            $this->data = $_POST;
            return $this;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $input = file_get_contents('php://input');

        if (str_contains($contentType, 'application/json')) {
            $this->data = json_decode($input, true) ?? [];
            return $this;
        }
        if (str_contains($contentType, 'application/x-www-form-urlencoded') && !empty($input)) {
            parse_str($input, $this->data);
            return $this;
        }
        $this->data = [];

        return $this;
    }

    private function fetchFiles(): self
    {
        $files = $_FILES;
        $result = [];

        foreach ($files as $field => $file) {

            if (!is_array($file['name'])) {
                $result[$field] = $file;
                continue;
            }

            $result[$field] = [];

            foreach (array_keys($file['name']) as $index) {
                $result[$field][] = [
                    'name'     => $file['name'][$index],
                    'type'     => $file['type'][$index],
                    'tmp_name' => $file['tmp_name'][$index],
                    'error'    => $file['error'][$index],
                    'size'     => $file['size'][$index],
                ];
            }
        }

        $this->files = $result;
        return $this;
    }

    private function fetchHeaders(): self
    {
        try {
            $this->headers = array_change_key_case(getallheaders(), CASE_LOWER);
        } catch (\Throwable $throwable) {
            $this->headers = [];
        }
        return $this;
    }

    private function fetchMethod(array $info): self
    {
        $method = strtoupper($info['REQUEST_METHOD'] ?? HttpMethodEnum::GET->value);
        if ($method === HttpMethodEnum::POST->value) {
            $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_POST['_method'] ?? null;
            if ($override !== null) {
                $method = strtoupper($override);
            }
        }
        $this->method = HttpMethodEnum::tryFrom($method) ?? HttpMethodEnum::GET;
        return $this;
    }

    private function fetchHost(array $info): self
    {
        $host = ($info['REQUEST_SCHEME'] ?? 'http') . '://' . ($info['HTTP_HOST'] ?? '');
        $this->host = trim($host, '/');
        return $this;
    }

    private function fetchSegments(array $info): self
    {
        $path = parse_url($info['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $this->segments = explode('/', trim($path, '/'));
        return $this;
    }

    private function fetchUrl(): self
    {
        $path = parse_url(
            $_SERVER['REQUEST_URI'] ?? '/',
            PHP_URL_PATH
        );

        $path ??= '/';

        if (config('app.resolver') !== 'subdomain') {
            $path = substr(trim($path, '/'), strlen($this->getModule()));
        }

        $this->url = trim($path, '/');

        return $this;
    }

    private function fetchQuery(array $info): self
    {
        $this->query = $info['QUERY_STRING'] ?? '';
        parse_str($this->query, $this->queries);
        return $this;
    }

}