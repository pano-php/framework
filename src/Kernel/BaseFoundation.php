<?php

namespace Pano\Kernel;

abstract readonly class BaseFoundation
{
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