<?php

namespace Pano\Kernel;

abstract readonly class BaseBoot
{
    abstract public function run(array $data): void;

    public function __construct(
        protected BaseFoundation $foundation,
        protected string $basePath,
        protected bool $debug,
        protected string $timezone,
    )
    {
        defined('BASE_PATH') ?: define("BASE_PATH", $basePath);

        $this->envLoader();
        $this->debug($debug);
        date_default_timezone_set($timezone);
    }

    protected function debug(bool $status): void
    {
        error_reporting(E_ERROR | E_PARSE);
        ini_set('display_errors', $status ? '1' : '0');
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

            // ignore comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);

            $name  = trim($name);
            $value = trim($value);

            // remove quotes
            $value = trim($value, '"\'');

            $parsed = $this->parseEnvValue($value);

            $_ENV[$name]    = $parsed;
            $_SERVER[$name] = $parsed;

            putenv("$name=$value");
        }
    }


    private function parseEnvValue(string $value): mixed
    {
        return match (strtolower($value)) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => is_numeric($value) ? $value + 0 : $value,
        };
    }
}
