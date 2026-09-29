<?php

namespace Pano\Kernel;

abstract readonly class BaseModule
{
    abstract public function routes(): BaseRouter;

    abstract public function view(): BaseView;

    abstract public function log(): BaseLogger;

    public function __construct(protected BaseRequest $request, public BaseFoundation $foundation)
    {
    }

    protected function viewPath(): string
    {
        return $this->path('Views');
    }

    protected function filePath(): string
    {
        return $this->path('Files');
    }

    protected function logPath(): string
    {
        return $this->path('Logs');
    }

    public function path(string $path = ''): string
    {
        $reflector = new \ReflectionClass(static::class);

        return dirname($reflector->getFileName()) . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
    }

    public function name(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
