<?php

namespace Pano\Foundation;

use Pano\Kernel\BaseRequest;
use Pano\Kernel\HttpMethodEnum;
use Pano\Kernel\ModuleResolverEnum;

class Request extends BaseRequest
{
    public function __construct(array $data, null|string $module = null)
    {
        $this->fetchMethod($data)
            ->fetchSegments($data)
            ->fetchQuery($data)
            ->fetchHost($data)
            ->setModule($module)
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
            return $this;
        }

        $byResolver = [
            ModuleResolverEnum::HOST->value      => [],
            ModuleResolverEnum::SUBDOMAIN->value => [],
            ModuleResolverEnum::PATH->value      => [],
            ModuleResolverEnum::QUERY->value     => [],
            ModuleResolverEnum::HEADER->value    => [],
        ];

        foreach ((FOUNDATION)::modules() as $key => $entry) {
            if ($key === '') {
                continue;
            }

            $resolver = is_array($entry)
                ? ($entry['resolver'] ?? ModuleResolverEnum::PATH)
                : ModuleResolverEnum::PATH;

            if ($resolver instanceof ModuleResolverEnum) {
                $resolver = $resolver->value;
            }

            if (isset($byResolver[$resolver])) {
                $byResolver[$resolver][] = $key;
            }
        }

        foreach ($byResolver as $resolver => $keys) {
            foreach ($keys as $key) {
                if ($this->matches($key, $resolver)) {
                    $this->module = $key;
                    return $this;
                }
            }
        }

        $this->module = '';
        return $this;
    }

    private function matches(string $key, string $resolver): bool
    {
        return match ($resolver) {
            ModuleResolverEnum::HOST->value      => $this->matchHost($key),
            ModuleResolverEnum::SUBDOMAIN->value => $this->matchSubdomain($key),
            ModuleResolverEnum::PATH->value      => $this->matchPath($key),
            ModuleResolverEnum::QUERY->value     => $this->matchQuery($key),
            ModuleResolverEnum::HEADER->value    => $this->matchHeader($key),
            default                              => false,
        };
    }

    private function matchHost(string $key): bool
    {
        $host = parse_url($this->host, PHP_URL_HOST);

        return $host !== null && $host !== '' && $host === $key;
    }

    private function matchSubdomain(string $key): bool
    {
        $host = parse_url($this->host, PHP_URL_HOST);
        $rootDomain = parse_url(config('app.url'), PHP_URL_HOST);

        if (empty($host) || empty($rootDomain)
            || $host === $rootDomain
            || !str_ends_with($host, '.' . $rootDomain)
        ) {
            return $key === '';
        }

        $subdomain = rtrim(substr($host, 0, -strlen($rootDomain)), '.');

        return $subdomain === $key;
    }

    private function matchPath(string $key): bool
    {
        $segment = $this->segments[0] ?? '';

        return $segment === $key;
    }

    private function matchQuery(string $key): bool
    {
        $value = $this->queries[(FOUNDATION)::param()] ?? null;

        return is_string($value) && $value === $key;
    }

    private function matchHeader(string $key): bool
    {
        $value = $this->headers[(FOUNDATION)::param()] ?? null;

        return is_string($value) && $value === $key;
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
        $path = trim($path, '/');

        $module = $this->getModule();

        if (($module !== '')
            && ((FOUNDATION)::resolver($module) === ModuleResolverEnum::PATH)
            && str_starts_with($path, $module)
        ) {
            $path = ltrim(substr($path, strlen($module)), '/');
        }

        $this->url = $path;

        return $this;
    }

    private function fetchQuery(array $info): self
    {
        $this->query = $info['QUERY_STRING'] ?? '';
        parse_str($this->query, $this->queries);
        return $this;
    }

}