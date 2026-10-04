<?php

namespace Pano\Kernel;

abstract readonly class BasePackage extends BaseModule
{

    public function __construct(BaseRequest $request, BaseFoundation $foundation)
    {
        parent::__construct($request, $foundation);
    }

    public function importPackages(): static
    {
        throw new ($this->foundation::exception())("importPackages unsupported by Package");
    }

}
