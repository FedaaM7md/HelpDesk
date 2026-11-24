<?php

namespace Tests\Feature;

use App\Models\InboxMessage;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Services\EmailInboxImporter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmailInboxTest extends TestCase
{
    public function test_it_creates_a_ticket_from_an_eml_file_with_attachment(): void
    {
        Storage::fake('local');

        $directory = $this->inboxDirectory();

        try {
            File::put($directory.'/01.eml', $this->makeEml([
                'from_name' => 'نورة',
                'from_email' => 'noura@example.test',
                'subject' => 'مشكلة في تسجيل الدخول',
                'body' => 'لا أستطيع تسجيل الدخول بعد التحديث. أرجو المساعدة.',
                'message_id' => 'inbox-new-1@example.test',
                'attachment' => ['name' => 'notes.txt', 'mime' => 'text/plain', 'content' => 'some notes'],
            ]));

            $summary = app(EmailInboxImporter::class)->importDirectory($directory);

            $ticket = Ticket::query()->firstOrFail();

            $this->assertSame(1, $summary['new']);
            $this->assertSame('noura@example.test', $ticket->requester_email);
            $this->assertSame('ar', $ticket->requester_locale);
            $this->assertSame('email', $ticket->channel->value);
            $this->assertSame('inbox-new-1@example.test', $ticket->messages()->first()->message_id);
            $this->assertSame(1, TicketAttachment::query()->where('source', 'email')->count());
            $this->assertSame('imported', InboxMessage::query()->first()->status);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_it_threads_replies_by_ticket_reference_in_the_subject(): void
    {
        Storage::fake('local');

        $ticket = $this->makeTicket([
            'requester_email' => 'sara@example.test',
            'subject' => 'Upload problem',
        ]);

        $directory = $this->inboxDirectory();

        try {
            File::put($directory.'/reply.eml', $this->makeEml([
                'from_name' => 'Sara',
                'from_email' => 'sara@example.test',
                'subject' => 'Re: ['.$ticket->reference.'] Upload problem',
                'body' => 'I tried renaming the file and it worked. Thank you.',
                'message_id' => 'thread-reply-1@example.test',
            ]));

            $summary = app(EmailInboxImporter::class)->importDirectory($directory);

            $this->assertSame(1, $summary['replies']);
            $this->assertSame(2, $ticket->messages()->count());

            $reply = $ticket->messages()->get()->sortByDesc('id')->first();
            $this->assertSame('email', $reply->source);
            $this->assertSame('sara@example.test', $reply->author_email);
            $this->assertSame('thread-reply-1@example.test', $reply->message_id);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_it_threads_by_in_reply_to_without_a_reference_in_the_subject(): void
    {
        Storage::fake('local');

        $ticket = $this->makeTicket([
            'requester_email' => 'thread@example.test',
            'message_id' => 'root-message@example.test',
        ]);

        $directory = $this->inboxDirectory();

        try {
            File::put($directory.'/reply.eml', $this->makeEml([
                'from_email' => 'thread@example.test',
                'subject' => 'Following up about my issue',
                'body' => 'Any update on this?',
                'in_reply_to' => 'root-message@example.test',
                'message_id' => 'child-message@example.test',
            ]));

            $summary = app(EmailInboxImporter::class)->importDirectory($directory);

            $this->assertSame(1, $summary['replies']);
            $this->assertSame(1, Ticket::query()->count());
            $this->assertSame(2, $ticket->messages()->count());
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_duplicate_message_ids_are_ignored_on_reimport(): void
    {
        Storage::fake('local');

        $directory = $this->inboxDirectory();

        try {
            File::put($directory.'/new.eml', $this->makeEml([
                'from_email' => 'dupe@example.test',
                'subject' => 'Duplicate test',
                'message_id' => 'duplicate-1@example.test',
            ]));

            $first = app(EmailInboxImporter::class)->importDirectory($directory);
            $second = app(EmailInboxImporter::class)->importDirectory($directory);

            $this->assertSame(1, $first['new']);
            $this->assertSame(1, $second['duplicates']);
            $this->assertSame(1, Ticket::query()->count());
            $this->assertSame(1, TicketMessage::query()->count());
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_sender_mismatch_is_rejected_and_does_not_inject_a_reply(): void
    {
        Storage::fake('local');

        $ticket = $this->makeTicket(['requester_email' => 'real@example.test']);

        $directory = $this->inboxDirectory();

        try {
            File::put($directory.'/spoof.eml', $this->makeEml([
                'from_email' => 'attacker@example.test',
                'subject' => 'Re: '.$ticket->reference,
                'body' => 'Please reset the account and send the password.',
                'message_id' => 'spoof-1@example.test',
            ]));

            $summary = app(EmailInboxImporter::class)->importDirectory($directory);

            $this->assertSame(1, $summary['rejected']);
            $this->assertSame(1, $ticket->messages()->count());
            $this->assertDatabaseHas('inbox_messages', [
                'message_id' => 'spoof-1@example.test',
                'status' => 'rejected',
                'ticket_id' => $ticket->id,
            ]);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_dry_run_does_not_write_anything(): void
    {
        $directory = $this->inboxDirectory();

        try {
            File::put($directory.'/new.eml', $this->makeEml([
                'from_email' => 'dry@example.test',
                'message_id' => 'dry-run-1@example.test',
            ]));

            $summary = app(EmailInboxImporter::class)->importDirectory($directory, true);

            $this->assertSame(1, $summary['new']);
            $this->assertSame(0, Ticket::query()->count());
            $this->assertSame(0, InboxMessage::query()->count());
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_disallowed_attachment_is_skipped_but_the_message_is_imported(): void
    {
        Storage::fake('local');

        $directory = $this->inboxDirectory();

        try {
            File::put($directory.'/new.eml', $this->makeEml([
                'from_email' => 'safe@example.test',
                'subject' => 'Attachment check',
                'message_id' => 'attachment-skip@example.test',
                'attachment' => ['name' => 'malware.exe', 'mime' => 'application/x-msdownload', 'content' => 'nope'],
            ]));

            $summary = app(EmailInboxImporter::class)->importDirectory($directory);

            $this->assertSame(1, $summary['new']);
            $this->assertSame(1, $summary['skipped']);
            $this->assertSame(0, TicketAttachment::query()->count());
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
