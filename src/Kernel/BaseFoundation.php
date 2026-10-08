<?php

namespace Pano\Kernel;

abstract class BaseFoundation
{
    protected static array $modules = [];

    final public static function resolver(string $key): ModuleResolverEnum
    {
        $entry = static::$modules[$key] ?? null;

        if (is_array($entry) && isset($entry['resolver'])) {
            return $entry['resolver'];
        }

        return ModuleResolverEnum::PATH;
    }

    final public static function module(string $key): ?string
    {
        $entry = static::$modules[$key] ?? null;

        if ($entry === null) {
            return null;
        }

        return is_array($entry) ? ($entry['class'] ?? null) : $entry;
    }

    final public static function modules(): array
    {
        return static::$modules;
    }

    public static function param(): string
    {
        return 'module';
    }

    /** @return class-string<BaseException> */
    abstract public static function exception(): string;

    /** @return class-string<BaseRequest> */
    abstract public static function request(): string;

    /** @return class-string<BaseRequest> */
    abstract public static function cliRequest(): string;

    /** @return class-string<BaseResponse> */
    abstract public static function response(): string;

    /** @return class-string<BaseRouter> */
    abstract public static function router(): string;

    /** @return class-string<BaseView> */
    abstract public static function view(): string;

    /** @return class-string<BaseLogger> */
    abstract public static function logger(): string;

    /** @return class-string<BaseBag> */
    abstract public static function bag(): string;
}