<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use App\Services\PageRenderContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResetPageRenderContext
{
    public function handle(Request $request, Closure $next): Response
    {
        app(PageRenderContext::class)->reset();
        app(CartService::class)->flushMemo();

        return $next($request);
    }
}
