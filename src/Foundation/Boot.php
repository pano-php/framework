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
        protected string $basePath,
        BaseFoundation   $foundation = new Foundation()
    )
    {
        define("BASE_PATH", $this->basePath);
        $this->envLoader();
        $this->configLoader();

        parent::__construct(
            foundation: $foundation,
            debug: config('app.debug', false),
            timezone: (string)config('app.timezone', 'UTC')
        );
    }

    public function run(array $data): void
    {
        if (PHP_SAPI === 'cli') {
            exit($this->dispatcher((FOUNDATION)::cliRequest(), $data));
        }

        $this->dispatcher((FOUNDATION)::request(), $data);
        exit(0);
    }

    protected function dispatcher($requestClass, ...$args): int
    {
        try {
            /** @var BaseRequest $request */
            $request = new $requestClass(...$args);
            $module = $request->getModule();
            $moduleName = (FOUNDATION)::module($module);
            if ($moduleName === null) {
                if (($module === '') || ($request->getMethod() === HttpMethodEnum::CLI)) {
                    throw new ((FOUNDATION)::exception())("No module found for '$module'");
                }
                $args[] = '';
                return $this->dispatcher($requestClass, ...$args);
            }
            if (!class_exists($moduleName)) {
                throw new ((FOUNDATION)::exception())("Module class ($moduleName) not found");
            }
            $reflection = new ReflectionClass($moduleName);
            if (!$reflection->isSubclassOf(BaseModule::class)) {
                throw new ((FOUNDATION)::exception())("Module ($moduleName) must extend " . BaseModule::class);
            }
            /** @var BaseModule $module */
            $module = ($reflection->newInstance($request, FOUNDATION));
            $module->setRouter(new ((FOUNDATION)::router())($request, $module))->importPackages()->setup();
            return $module->getRouter()->handle();
        } catch (\Throwable $e) {
            return ((FOUNDATION)::response())::exception($e, $request)->send();
        }
    }

    protected function configLoader(): void
    {
        $configs = [];
        $configPath = $this->basePath . '/config';

        if (is_dir($configPath)) {
            foreach (glob($configPath . '/*.php') as $file) {
                $name = basename($file, '.php');
                $configs[$name] = require $file;
            }
        }
        $_ENV['#_configs_#'] = $configs;
    }

    protected function envLoader(): void
    {
        $envFilePath = $this->basePath . '.env';

        if (!is_file($envFilePath)) {
            return;
        }

        $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {

            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);

            $name = trim($name);
            $value = trim($value);

            $value = trim($value, '"\'');

            $parsed = $this->parseEnvValue($value);

            $_ENV[$name] = $parsed;

            putenv("$name=$value");
        }
    }

    private function parseEnvValue(string $value): mixed
    {
        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => is_numeric($value) ? $value + 0 : $value,
        };
    }

}
