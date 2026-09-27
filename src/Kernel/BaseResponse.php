<?php

namespace Pano\Kernel;

abstract class BaseResponse
{
    abstract public function send(): int;

    protected HttpStatusEnum|ResultCodeEnum $status = HttpStatusEnum::OK;
    protected array $headers = [];
    protected mixed $body = null;

    public function setStatus(HttpStatusEnum|ResultCodeEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        foreach ($headers as $k => $v) {
            $this->setHeader($k, $v);
        }
        return $this;
    }

    public function setBody(mixed $body): self
    {
        $this->body = $body;
        return $this;
    }

}
