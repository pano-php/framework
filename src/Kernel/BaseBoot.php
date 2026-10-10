<?php

namespace Pano\Kernel;

abstract readonly class BaseBoot
{
    abstract public function run(array $data): void;

    public function __construct(
        protected BaseFoundation $foundation,
        protected bool $debug,
        protected string $timezone,
    )
    {
        define("FOUNDATION", $foundation);

        $this->debug($debug);
        date_default_timezone_set($timezone);
    }

    protected function debug(bool $status): void
    {
        error_reporting(E_ERROR | E_PARSE);
        ini_set('display_errors', $status ? '1' : '0');
    }

}
