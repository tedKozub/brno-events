<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach OWASP recommended defensive security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Remove information disclosure header
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        $response->headers->remove('X-Powered-By');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Content Security Policy in Report-Only mode as recommended by security audit
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https:",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "upgrade-insecure-requests",
        ];
        $response->headers->set('Content-Security-Policy-Report-Only', implode('; ', $csp));

        // Allow Back/Forward Cache (bfcache) for public GET requests while ensuring freshness on direct visits
        if ($request->isMethod('GET') && !$request->is('admin*')) {
            $response->headers->set('Cache-Control', 'private, no-cache, must-revalidate');
        }

        return $response;
    }
}
