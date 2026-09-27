<?php

namespace Pano\Foundation;

use Pano\Kernel\BaseBoot;
use Pano\Kernel\BaseModule;
use Pano\Kernel\BaseRequest;
use Pano\Kernel\HttpMethodEnum;
use ReflectionClass;

final class Boot extends BaseBoot
{

    public function run(array $data, bool $cli = false): void
    {
        if ($cli) {
            exit($this->dispatcher(CLIRequest::class, $data));
        }

        $this->dispatcher(Request::class, $data);
        exit(0);
    }

    protected function dispatcher($requestClass, ...$args): int
    {
        /** @var BaseRequest $request */
        $request = new $requestClass(...$args);
        try {
            $module = $request->getModule();
            $moduleName = config('modules.' . $module, null);
            if ($moduleName === null) {
                if (($module === '') || ($request->getMethod() === HttpMethodEnum::CLI)) {
                    throw new Exception("No module found for '$module'");
                }
                $args[] = '';
                return $this->dispatcher($requestClass, ...$args);
            }
            if (!class_exists($moduleName)) {
                throw new Exception("Module class ($moduleName) not found");
            }
            $reflection = new ReflectionClass($moduleName);
            if (!$reflection->isSubclassOf(BaseModule::class)) {
                throw new Exception("Module ($moduleName) must extend " . BaseModule::class);
            }
            return $reflection->newInstance($request)->routes()->handle();
        } catch (\Throwable $e) {
            return Response::exception($e, $request)->send();
        }
    }

}
