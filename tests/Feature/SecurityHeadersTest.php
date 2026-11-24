<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_baseline_security_headers_and_csp_are_present(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $this->scriptDirective($csp));
        $this->assertStringNotContainsString('unsafe-eval', $this->scriptDirective($csp));
        $this->assertStringNotContainsString('5173', $csp);
    }

    public function test_hsts_header_is_not_sent_over_plain_http(): void
    {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
    }

    protected function scriptDirective(string $csp): string
    {
        foreach (explode(';', $csp) as $directive) {
            if (str_contains(trim($directive), 'script-src')) {
                return trim($directive);
            }
        }

        return '';
    }
}
