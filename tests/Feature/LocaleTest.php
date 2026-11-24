<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleTest extends TestCase
{
    public function test_default_locale_is_arabic_and_rtl(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('بماذا نساعدك اليوم؟');
    }

    public function test_english_session_switches_direction_and_copy(): void
    {
        $this->withSession(['locale' => 'en'])->get('/')
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertSee('What do you need help with?');
    }

    public function test_language_switch_persists_and_redirects_back(): void
    {
        $response = $this->withHeader('referer', config('app.url').'/kb')
            ->get('/language/en');

        $response->assertRedirect(config('app.url').'/kb');
        $this->assertSame('en', session('locale'));

        $this->get('/kb')->assertSee('lang="en" dir="ltr"', false);

        $this->get('/language/fr')->assertNotFound();
    }

    public function test_validation_messages_are_localized_in_arabic(): void
    {
        $response = $this->from('/submit')->post('/submit', []);

        $response->assertSessionHasErrors(['requester_name', 'requester_email', 'subject', 'category_id', 'message']);

        $message = session('errors')->first('requester_name');
        $this->assertStringContainsString('الاسم', $message);
    }

    public function test_public_pages_render_without_missing_translation_keys(): void
    {
        $this->get('/')->assertOk()->assertDontSee('common.');
        $this->get('/submit')->assertOk()->assertDontSee('portal.submit.title');
        $this->get('/track')->assertOk()->assertDontSee('portal.track.title');
        $this->get('/kb')->assertOk()->assertDontSee('kb.title');
    }
}
