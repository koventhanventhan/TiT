<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware extends ResolveTenant
{
    // Inherits all host-based resolution, safety nets, status, and expiry checks from ResolveTenant
}
