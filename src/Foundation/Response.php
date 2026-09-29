<?php

namespace Pano\Foundation;

use Pano\Kernel\BaseException;
use Pano\Kernel\BaseRequest;
use Pano\Kernel\BaseResponse;
use Pano\Kernel\HttpMethodEnum;
use Pano\Kernel\HttpStatusEnum;
use Pano\Kernel\ResultCodeEnum;

class Response extends BaseResponse
{
    private bool $sent = false;

    public static function make(
        mixed          $body = null,
        HttpStatusEnum $status = HttpStatusEnum::OK,
        array          $headers = []
    ): self {
        return (new self())
            ->setStatus($status)
            ->setHeaders($headers)
            ->setBody($body);
    }

    public static function json(
        array|object   $data,
        HttpStatusEnum $status = HttpStatusEnum::OK,
        array          $headers = []
    ): self {
        return (new self())
            ->setStatus($status)
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setHeaders($headers)
            ->setBody(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public static function text(
        string         $text,
        HttpStatusEnum $status = HttpStatusEnum::OK,
        array          $headers = []
    ): self {
        return (new self())
            ->setStatus($status)
            ->setHeader('Content-Type', 'text/plain; charset=utf-8')
            ->setHeaders($headers)
            ->setBody($text);
    }

    public static function html(
        string         $html,
        HttpStatusEnum $status = HttpStatusEnum::OK,
        array          $headers = []
    ): self {
        return (new self())
            ->setStatus($status)
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->setHeaders($headers)
            ->setBody($html);
    }

    public static function stream(
        callable       $callback,
        string         $contentType = 'application/octet-stream',
        HttpStatusEnum $status = HttpStatusEnum::OK,
        array          $headers = []
    ): self {
        return (new self())
            ->setStatus($status)
            ->setHeader('Content-Type', $contentType)
            ->setHeaders($headers)
            ->setBody($callback);
    }

    public static function redirect(
        string         $to,
        HttpStatusEnum $status = HttpStatusEnum::FOUND,
        array          $headers = []
    ): self {
        return (new self())
            ->setStatus($status)
            ->setHeader('Location', $to)
            ->setHeaders($headers)
            ->setBody(
                '<html><head><meta http-equiv="refresh" content="0;url=' .
                htmlspecialchars($to, ENT_QUOTES) .
                '"></head></html>'
            );
    }

    public static function terminal(
        string $text,
        ResultCodeEnum $status = ResultCodeEnum::OK
    ): self {
        $text = $status === ResultCodeEnum::OK ? "\033[32m$text\033[0m\n" : "\033[31m$text\033[0m\n";
        return (new self())
            ->setStatus($status)
            ->setBody($text);
    }

    public static function exception(
        \Throwable $e,
        BaseRequest $request
    ): self {
        $debug = config('app.debug', false);

        if ($e instanceof BaseException) {

            if ($request->getMethod() === HttpMethodEnum::CLI) {
                return self::terminal($e->getMessage(), ResultCodeEnum::ERROR);
            }

            if ($request->expectsJson()) {
                return self::json(
                    $e->toArray($debug),
                    $e->status()
                );
            }

            return self::html(
                $e->toHtml($debug),
                $e->status()
            );
        }

        return self::text(
            $debug ? $e->getMessage() : 'Server Error',
            HttpStatusEnum::INTERNAL_SERVER_ERROR
        );
    }

    public function send(): int
    {
        if ($this->sent) {
            return $this->status->value;
        }

        if ($this->status instanceof HttpStatusEnum) {
            http_response_code($this->status->value);

            foreach ($this->headers as $key => $value) {
                header("$key: $value", true);
            }
        }

        if (is_callable($this->body)) {
            ($this->body)();
        } else {
            echo $this->body;
        }

        $this->sent = true;
        return $this->status->value;
    }

}
