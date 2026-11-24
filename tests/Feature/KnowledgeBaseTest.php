<?php

namespace Tests\Feature;

use App\Enums\KbStatus;
use App\Models\KbArticle;
use App\Models\KbCategory;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    protected function kbCategory(): KbCategory
    {
        return KbCategory::firstOrCreate(
            ['slug' => 'help'],
            [
                'name_ar' => 'المساعدة',
                'name_en' => 'Help',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
    }

    protected function article(array $attributes): KbArticle
    {
        return KbArticle::create(array_merge([
            'kb_category_id' => $this->kbCategory()->id,
            'translation_key' => 'test-group',
            'locale' => 'ar',
            'title' => 'مقال اختبار',
            'slug' => 'test-article-ar',
            'excerpt' => 'مقتطف',
            'body' => 'محتوى المقال التفصيلي للاختبار.',
            'status' => KbStatus::Published,
            'published_at' => now()->subDay(),
            'views' => 0,
        ], $attributes));
    }

    public function test_public_index_only_shows_published_articles_for_the_active_locale(): void
    {
        $publishedAr = $this->article(['title' => 'كيف أتابع تذكرتي', 'slug' => 'track-ar', 'translation_key' => 'track']);
        $publishedEn = $this->article([
            'title' => 'How to track my ticket',
            'slug' => 'track-en',
            'locale' => 'en',
            'translation_key' => 'track',
        ]);
        $draft = $this->article([
            'title' => 'مسودة سرية',
            'slug' => 'secret-draft',
            'translation_key' => 'secret',
            'status' => KbStatus::Draft,
            'published_at' => null,
            'body' => 'uniquedraftghost content',
        ]);

        $this->get('/kb')
            ->assertOk()
            ->assertSee($publishedAr->title)
            ->assertDontSee($publishedEn->title)
            ->assertDontSee($draft->title);

        $this->get('/kb/'.$draft->slug)->assertNotFound();
        $this->get('/kb/'.$publishedEn->slug)->assertNotFound();

        $this->withSession(['locale' => 'en'])->get('/kb/'.$publishedEn->slug)->assertOk();
    }

    public function test_search_does_not_leak_draft_articles(): void
    {
        $this->article(['title' => 'دليل المنشور', 'slug' => 'published-guide', 'translation_key' => 'guide']);
        $ghost = $this->article([
            'title' => 'مسودة البحث',
            'slug' => 'search-draft',
            'translation_key' => 'search-draft',
            'status' => KbStatus::Draft,
            'published_at' => null,
            'body' => 'uniquedraftghost appears only here',
        ]);

        $this->get('/kb?q=uniquedraftghost')->assertOk()->assertDontSee($ghost->title);
        $this->get('/kb?q=دليل')->assertOk()->assertSee('دليل المنشور');
    }

    public function test_category_page_lists_only_published_articles(): void
    {
        $this->article(['title' => 'مقال التصنيف', 'slug' => 'category-article', 'translation_key' => 'cat']);
        $this->article([
            'title' => 'مسودة التصنيف',
            'slug' => 'category-draft',
            'translation_key' => 'cat-draft',
            'status' => KbStatus::Draft,
            'published_at' => null,
        ]);

        $this->get('/kb/category/help')
            ->assertOk()
            ->assertSee('مقال التصنيف')
            ->assertDontSee('مسودة التصنيف');
    }

    public function test_supervisor_can_publish_a_draft_article(): void
    {
        $draft = $this->article([
            'title' => 'سيُنشر قريبًا',
            'slug' => 'soon-published',
            'translation_key' => 'soon',
            'status' => KbStatus::Draft,
            'published_at' => null,
        ]);

        $this->actingAs($this->staff('supervisor'))
            ->post('/app/kb/'.$draft->slug.'/publish')
            ->assertRedirect();

        $draft->refresh();
        $this->assertSame(KbStatus::Published, $draft->status);
        $this->assertNotNull($draft->published_at);

        $this->get('/kb/'.$draft->slug)->assertOk();
    }

    public function test_supervisor_can_create_a_bilingual_article_and_agent_cannot(): void
    {
        $category = $this->kbCategory();
        $supervisor = $this->staff('supervisor');

        $payload = [
            'kb_category_id' => $category->id,
            'locale' => 'ar',
            'translation_key' => 'created-group',
            'title' => 'مقال جديد من المشرف',
            'slug' => 'supervisor-created',
            'excerpt' => 'مقتطف جديد',
            'body' => 'هذا محتوى كافٍ لاجتياز التحقق الخاص بالمقال الجديد.',
            'status' => 'published',
        ];

        $this->actingAs($supervisor)->post('/app/kb', $payload)->assertRedirect();
        $this->assertDatabaseHas('kb_articles', ['slug' => 'supervisor-created', 'status' => 'published']);

        $this->actingAs($this->staff('agent'))->post('/app/kb', $payload)->assertForbidden();
    }
}
