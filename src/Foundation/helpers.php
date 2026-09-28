<?php

if (!function_exists('dd')) {
    function dd(...$args): void
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
}

if (!function_exists('url')) {
    function url(string $path): string
    {
        return trim(config('app.url'), '/') . '/' . trim($path, '/');
    }
}

if (!function_exists('env')) {

    function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key]
            ?? $_SERVER[$key]
            ?? $default;
    }
}

if (!function_exists('config')) {

    function config(string $key, mixed $default = null): mixed
    {
        static $configs = [];

        if (empty($configs)) {
            $configPath = BASE_PATH . '/config';

            if (is_dir($configPath)) {
                foreach (glob($configPath . '/*.php') as $file) {
                    $name = basename($file, '.php');
                    $configs[$name] = require $file;
                }
            }
        }

        $result = $configs;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($result) || !array_key_exists($segment, $result)) {
                return $default;
            }
            $result = $result[$segment];
        }
        return $result;
    }

}

if (!function_exists('currentUrl')) {
    function currentUrl(): string
    {
        return trim(url($_SERVER['REQUEST_URI'] ?? '/'), '/');
    }
}

