<?php

namespace Pano\Foundation;

use Pano\Kernel\BaseBoot;
use Pano\Kernel\BaseFoundation;
use Pano\Kernel\BaseModule;
use Pano\Kernel\BaseRequest;
use Pano\Kernel\HttpMethodEnum;
use ReflectionClass;

final readonly class Boot extends BaseBoot
{
    public function __construct(
        string $basePath,
        BaseFoundation $foundation = new Foundation()
    )
    {
        define("BASE_PATH", $basePath);
        parent::__construct(
            foundation: $foundation,
            basePath: $basePath,
            debug: config('app.debug', false),
            timezone: (string) config('app.timezone', 'UTC')
        );
    }

    public function run(array $data): void
    {
        if (PHP_SAPI === 'cli') {
            exit($this->dispatcher($this->foundation::cliRequest(), $data));
        }

        $this->dispatcher($this->foundation::request(), $data);
        exit(0);
    }

    protected function dispatcher($requestClass, ...$args): int
    {
        /** @var BaseRequest $request */
        $request = new $requestClass(...$args);
        try {
            $module = $request->getModule();
            $moduleName = config('modules.' . $module, null);
            if ($moduleName === null) {
                if (($module === '') || ($request->getMethod() === HttpMethodEnum::CLI)) {
                    throw new ($this->foundation::exception())("No module found for '$module'");
                }
                $args[] = '';
                return $this->dispatcher($requestClass, ...$args);
            }
            if (!class_exists($moduleName)) {
                throw new ($this->foundation::exception())("Module class ($moduleName) not found");
            }
            $reflection = new ReflectionClass($moduleName);
            if (!$reflection->isSubclassOf(BaseModule::class)) {
                throw new ($this->foundation::exception())("Module ($moduleName) must extend " . BaseModule::class);
            }
            /** @var BaseModule $module */
            $module = ($reflection->newInstance($request, $this->foundation));
            $module->setRouter(new Router($request, $module))->importPackages()->setup();
            return $module->getRouter()->handle();
        } catch (\Throwable $e) {
            return ($this->foundation::response())::exception($e, $request)->send();
        }
    }

}
