<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TypographyTest extends TestCase
{
    public function test_arabic_typography_guard_is_present_in_css(): void
    {
        $css = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString("[dir='rtl'] .eyebrow", $css);
        $this->assertStringContainsString("[dir='rtl'] .table-head", $css);
        $this->assertStringContainsString("[dir='rtl'] [class*='tracking-']", $css);
        $this->assertStringContainsString('letter-spacing: 0', $css);
        $this->assertStringContainsString('text-transform: none', $css);
    }

    public function test_arabic_pages_render_rtl_with_arabic_eyebrows(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('مركز دعم شبكة فيبر نت');
    }

    public function test_english_pages_keep_ltr(): void
    {
        $this->withSession(['locale' => 'en'])->get('/')
            ->assertOk()
            ->assertSee('dir="ltr"', false);
    }
}
