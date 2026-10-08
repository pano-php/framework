<?php

namespace Pano\Kernel;

enum ModuleResolverEnum: string
{
    case HOST = 'host';
    case SUBDOMAIN = 'subdomain';
    case PATH = 'path';
    case QUERY = 'query';
    case HEADER = 'header';
}