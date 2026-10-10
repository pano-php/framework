<?php

namespace Pano\Foundation;

use Pano\Kernel\ModuleResolverEnum;

final class Support
{

    public static function dd(...$args): void
    {
        $isCli = PHP_SAPI === 'cli';

        if ($isCli) {
            // ===== CLI OUTPUT =====
            foreach ($args as $i => $arg) {
                echo PHP_EOL;
                echo "\033[1;36m══════════════════════════════════════\033[0m" . PHP_EOL;
                echo "\033[1;33m[DATA {$i}]\033[0m" . PHP_EOL;
                echo "\033[1;36m──────────────────────────────────────\033[0m" . PHP_EOL;

                if (is_scalar($arg) || $arg === null) {
                    var_dump($arg);
                } else {
                    print_r($arg);
                }

                echo "\033[1;36m══════════════════════════════════════\033[0m" . PHP_EOL;
            }

            exit(1);
        }

        // ===== WEB OUTPUT =====
        echo '<pre style="
            background:#111;
            color:#eee;
            padding:16px;
            border-radius:8px;
            font-size:14px;
            line-height:1.5;
            overflow:auto;
        ">';

        foreach ($args as $i => $arg) {
            echo "<strong>DATA {$i}:</strong>\n";
            echo htmlspecialchars(var_export($arg, true)) . "\n\n";
        }

        echo '</pre>';
        exit;
    }


    public static function url(string $path, ?string $moduleParam = null): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? config('app.url');
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
        $query = trim($_SERVER['QUERY_STRING'] ?? '');
        $path = trim(trim($path), '/');
        if ($moduleParam !== null) {
            $moduleParam = trim(trim($moduleParam), '/');
            $module = (FOUNDATION)::modules()[$moduleParam];
            $type = ($module['resolver'] ?? ModuleResolverEnum::PATH)->value;
        } else {
            $moduleParam = '';
            $type = null;
        }

        return match ($type) {
            ModuleResolverEnum::HOST->value => "$scheme://$moduleParam/$path",
            ModuleResolverEnum::SUBDOMAIN->value => $moduleParam === '' ? "$scheme://$host/$path" : "$scheme://$moduleParam.$host/$path",
            ModuleResolverEnum::QUERY->value => "$scheme://$host/$path?$query" . ($query === '' ? '?' : '&') . (FOUNDATION)::param() . "=$moduleParam",
            ModuleResolverEnum::PATH->value => $moduleParam === '' ? "$scheme://$host/$path" : "$scheme://$host/$moduleParam/$path",
            ModuleResolverEnum::HEADER->value => "$scheme://$host/$path",
            default => "$scheme://$host/$path",
        };
    }

    public static function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key]
            ?? $_SERVER[$key]
            ?? $default;
    }

    public static function config(string $key, mixed $default = null): mixed
    {
        $result = env('#_configs_#', []);

        foreach (explode('.', $key) as $segment) {
            if (!is_array($result) || !array_key_exists($segment, $result)) {
                return $default;
            }
            $result = $result[$segment];
        }
        return $result;
    }

    public static function currentUrl(): string
    {
        return trim(url($_SERVER['REQUEST_URI'] ?? '/'), '/');
    }

    public static function path(string $path): string
    {
        return BASE_PATH . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
    }

}