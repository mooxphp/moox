<?php

use Laravel\Mcp\Facades\Mcp;
use Moox\Mcp\Servers\BuildServer;
use Moox\Mcp\Servers\CoreServer;
use Moox\Mcp\Servers\PackageServer;

Mcp::local('moox-core', CoreServer::class);
Mcp::local('moox-build', BuildServer::class);
Mcp::local('moox-package', PackageServer::class);
