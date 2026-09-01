<?php

use App\Mcp\Servers\OpenAirMetricsServer;
use Laravel\Mcp\Facades\Mcp;

// Registra o servidor para acesso local via CLI/Antigravity
Mcp::local('openair-local', OpenAirMetricsServer::class);
