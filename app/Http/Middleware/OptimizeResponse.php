<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptimizeResponse
{
    /**
     * Handle an incoming request.
     * Optimizes the response by adding caching headers and compressing content.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip for non-successful responses or file downloads
        if (! $response->isSuccessful() || $response->headers->has('Content-Disposition')) {
            return $response;
        }

        // Add cache headers for static assets
        if ($this->isStaticAsset($request)) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        }

        // Add ETag for dynamic content
        if ($response->headers->get('Content-Type') === 'text/html; charset=UTF-8') {
            $content = $response->getContent();
            if ($content) {
                $etag = md5($content);
                $response->headers->set('ETag', '"'.$etag.'"');

                // Check if client has cached version
                if ($request->header('If-None-Match') === '"'.$etag.'"') {
                    $response->setStatusCode(304);
                    $response->setContent('');
                }
            }
        }

        return $response;
    }

    /**
     * Check if request is for a static asset.
     */
    private function isStaticAsset(Request $request): bool
    {
        $path = $request->path();
        $staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];

        foreach ($staticExtensions as $ext) {
            if (str_ends_with($path, '.'.$ext)) {
                return true;
            }
        }

        return false;
    }
}
