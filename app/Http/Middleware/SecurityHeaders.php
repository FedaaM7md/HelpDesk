<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    protected function contentSecurityPolicy(): string
    {
        // Alpine is loaded from its CSP build (@alpinejs/csp), which parses
        // expressions without eval(). Scripts are therefore 'self' only.
        $script = "'self'";
        $connect = "'self'";

        // Allow the Vite dev server only while it is actually running (hot file)
        // and only for the two valid local hosts.
        $origin = $this->viteDevOrigin();

        if ($origin !== null) {
            $script .= ' '.$origin['http'];
            $connect .= ' '.$origin['http'].' '.$origin['ws'];
        }

        $directives = [
            "default-src 'self'",
            'script-src '.$script,
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            'connect-src '.$connect,
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        return implode('; ', $directives);
    }

    /**
     * @return array{http: string, ws: string}|null
     */
    protected function viteDevOrigin(): ?array
    {
        if (! app()->environment('local') || ! config('app.debug')) {
            return null;
        }

        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return null;
        }

        $url = trim((string) file_get_contents($hotFile));
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return null;
        }

        if (! in_array($parts['host'], ['localhost', '127.0.0.1'], true)) {
            return null;
        }

        $scheme = $parts['scheme'] ?? 'http';
        $port = $parts['port'] ?? 5173;
        $http = $scheme.'://'.$parts['host'].':'.$port;
        $ws = 'ws://'.$parts['host'].':'.$port;

        return ['http' => $http, 'ws' => $ws];
    }
}
