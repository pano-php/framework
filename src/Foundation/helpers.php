<?php

use Pano\Foundation\Support;

if (!function_exists('dd')) {
    function dd(...$args): void
    {
        Support::dd($args);
    }
}

if (!function_exists('url')) {
    function url(string $path): string
    {
        return Support::url($path);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Support::env($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Support::config($key, $default);
    }
}

if (!function_exists('currentUrl')) {
    function currentUrl(): string
    {
        return Support::currentUrl();
    }
}

