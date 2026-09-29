<?php

namespace Pano\Foundation;

use Pano\Kernel\BaseLogger;
use Pano\Kernel\LogLevelEnum;

class Logger extends BaseLogger
{
    public function __construct(string $path)
    {
        $filePath = $path . DIRECTORY_SEPARATOR . 'log-' . date('Y-m-d') . '.log';
        parent::__construct($filePath);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevelEnum::INFO, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevelEnum::ERROR, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevelEnum::DEBUG, $message, $context);
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(LogLevelEnum::EMERGENCY, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(LogLevelEnum::ALERT, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevelEnum::CRITICAL, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevelEnum::WARNING, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(LogLevelEnum::NOTICE, $message, $context);
    }
}
