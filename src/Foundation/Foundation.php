<?php

namespace Pano\Foundation;

use Pano\Kernel\BaseFoundation;

readonly class Foundation extends BaseFoundation
{
    public static function exception(): string
    {
        return Exception::class;
    }

    public static function request(): string
    {
        return Request::class;
    }

    public static function cliRequest(): string
    {
        return CliRequest::class;
    }

    public static function response(): string
    {
        return Response::class;
    }

    public static function router(): string
    {
        return Router::class;
    }

    public static function view(): string
    {
        return View::class;
    }

    public static function logger(): string
    {
        return Logger::class;
    }

    public static function bag(): string
    {
        return Bag::class;
    }
}
