<?php

namespace Tests;

use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Models\BusinessHour;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'helpdesk.timezone' => 'UTC',
            'helpdesk.demo' => false,
            'mail.default' => 'array',
        ]);

        Mail::fake();
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function staff(string $role = 'agent', array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'locale' => 'ar',
            'is_active' => true,
            'password' => 'Secret12345!',
        ], $attributes));
    }

    protected function category(string $slug = 'general-test'): TicketCategory
    {
        return TicketCategory::firstOrCreate(
            ['slug' => $slug],
            [
                'name_ar' => 'تصنيف اختبار',
                'name_en' => 'Test category',
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
    }

    protected function configureSla(bool $businessHours = false): void
    {
        foreach ([
            'urgent' => [60, 480],
            'high' => [240, 1440],
            'normal' => [480, 2880],
            'low' => [1440, 4320],
        ] as $priority => [$first, $resolution]) {
            SlaPolicy::updateOrCreate(
                ['priority' => $priority],
                [
                    'first_response_minutes' => $first,
                    'resolution_minutes' => $resolution,
                    'use_business_hours' => $businessHours,
                    'is_active' => true,
                ],
            );
        }
    }

    protected function configureBusinessHours(): void
    {
        foreach (range(0, 6) as $weekday) {
            BusinessHour::updateOrCreate(
                ['weekday' => $weekday],
                [
                    'opens_at' => '09:00',
                    'closes_at' => '17:00',
                    'is_enabled' => $weekday >= 0 && $weekday <= 4,
                ],
            );
        }
    }

    protected function makeTicket(array $overrides = [], bool $notify = false): Ticket
    {
        return app(TicketService::class)->create(array_merge([
            'subject' => 'Sample support issue',
            'description' => 'A detailed description of the reported problem for testing.',
            'requester_name' => 'Test Requester',
            'requester_email' => 'requester@example.test',
            'requester_locale' => 'ar',
            'category_id' => $this->category()->id,
            'priority' => TicketPriority::Normal,
            'channel' => TicketChannel::Portal,
        ], $overrides), [], null, $notify);
    }

    protected function inboxDirectory(): string
    {
        $path = storage_path('app/testing-inbox-'.uniqid());

        File::ensureDirectoryExists($path);

        return $path;
    }

    protected function makeEml(array $options = []): string
    {
        $fromName = $options['from_name'] ?? 'Test Sender';
        $fromEmail = $options['from_email'] ?? 'sender@example.test';
        $subject = $options['subject'] ?? 'A new support request';
        $messageId = $options['message_id'] ?? 'test-'.uniqid().'@example.test';
        $body = $options['body'] ?? 'Hello, I need help with my account.';
        $attachment = $options['attachment'] ?? null;
        $inReplyTo = $options['in_reply_to'] ?? null;

        $headers = [
            'From: '.$fromName.' <'.$fromEmail.'>',
            'To: support@helpdesk.test',
            'Subject: '.$subject,
            'Date: Mon, 23 Nov 2026 09:00:00 +0000',
            'Message-ID: <'.$messageId.'>',
            'MIME-Version: 1.0',
        ];

        if ($inReplyTo) {
            $headers[] = 'In-Reply-To: <'.$inReplyTo.'>';
            $headers[] = 'References: <'.$inReplyTo.'>';
        }

        if ($attachment === null) {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: 8bit';

            return implode("\n", $headers)."\n\n".$body."\n";
        }

        $headers[] = 'Content-Type: multipart/mixed; boundary="test-boundary"';

        return implode("\n", $headers)."\n\n"
            ."--test-boundary\n"
            ."Content-Type: text/plain; charset=UTF-8\n"
            ."Content-Transfer-Encoding: 8bit\n\n"
            .$body."\n"
            ."--test-boundary\n"
            .'Content-Type: '.($attachment['mime'] ?? 'text/plain').'; name="'.($attachment['name'] ?? 'note.txt')."\"\n"
            .'Content-Disposition: attachment; filename="'.($attachment['name'] ?? 'note.txt')."\"\n"
            ."Content-Transfer-Encoding: base64\n\n"
            .base64_encode($attachment['content'] ?? 'attachment content')."\n"
            ."--test-boundary--\n";
    }
}
