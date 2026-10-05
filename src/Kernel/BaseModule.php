<?php

namespace Pano\Kernel;

use ReflectionClass;

abstract readonly class BaseModule
{
    abstract public function setup(): void;

    abstract public function view(): BaseView;

    abstract public function log(): BaseLogger;

    protected BaseRouter $router;

    public function __construct(
        protected BaseRequest $request,
        public BaseFoundation $foundation,
        public array $packages = [],
    )
    {
    }

    public function setRouter(BaseRouter $router): static
    {
        $router->setModule($this);
        if (!isset($this->router)) {
            $this->router = $router;
        }
        return $this;
    }

    public function getRouter(): BaseRouter
    {
        return $this->router;
    }

    public function importPackages(): static
    {
        try {
            foreach ($this->packages as $package => $parameters) {
                if (is_numeric($package)) {
                    $package = $parameters;
                    $parameters = [];
                }
                if (!class_exists($package)) {
                    throw new ((FOUNDATION)::exception())("Package ($package) not exists");
                }

                $reflection = new ReflectionClass($package);
                if (!$reflection->isSubclassOf(BaseModule::class)) {
                    throw new ((FOUNDATION)::exception())("Module ($package) must extend " . BasePackage::class);
                }
                /** @var BasePackage $package */
                $packageClass = ($reflection->newInstance($this->request, $this->foundation, ...$parameters));
                $packageClass->setRouter($this->router)->setup();
            }
            $this->setRouter($this->router);
        } catch (\Throwable $exception) {
            throw new ((FOUNDATION)::exception())($exception->getMessage() . PHP_EOL . $exception->getTraceAsString());
        }
        return $this;
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
