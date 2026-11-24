<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_about_privacy_and_terms_pages_render(): void
    {
        foreach (['/about', '/privacy', '/terms'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('HelpDesk')
                ->assertSee('شبكة فيبر نت');
        }
    }

    public function test_pages_render_in_english(): void
    {
        $this->withSession(['locale' => 'en'])->get('/about')
            ->assertOk()
            ->assertSee('About')
            ->assertSee('FiberNet ISP');
    }

    public function test_footer_credit_links_to_owner_site(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('fedaamohamed.com', false)
            ->assertSee(__('common.credit'));
    }
}
