<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    public function test_a_valid_attachment_is_private_and_linked_to_the_ticket(): void
    {
        Storage::fake('local');

        $response = $this->post('/submit', [
            'requester_name' => 'Attachment Tester',
            'requester_email' => 'attach@example.test',
            'subject' => 'A valid attachment',
            'category_id' => $this->category()->id,
            'message' => 'Please find the requested log file attached to this message.',
            'attachments' => [UploadedFile::fake()->create('log.txt', 8, 'text/plain')],
        ]);

        $response->assertRedirect();

        $attachment = TicketAttachment::query()->firstOrFail();

        $this->assertSame('log.txt', $attachment->original_name);
        $this->assertSame('text/plain', $attachment->mime_type);
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertStringStartsWith('tickets/', $attachment->path);
    }

    public function test_disallowed_attachment_types_are_rejected(): void
    {
        $response = $this->from('/submit')->post('/submit', [
            'requester_name' => 'Bad File',
            'requester_email' => 'bad@example.test',
            'subject' => 'Executable upload',
            'category_id' => $this->category()->id,
            'message' => 'This message tries to upload an executable file.',
            'attachments' => [UploadedFile::fake()->create('shell.php', 2, 'application/x-php')],
        ]);

        $response->assertSessionHasErrors('attachments.0');
        $this->assertSame(0, Ticket::query()->count());
    }

    public function test_oversized_attachments_are_rejected(): void
    {
        $response = $this->from('/submit')->post('/submit', [
            'requester_name' => 'Big File',
            'requester_email' => 'big@example.test',
            'subject' => 'Too large',
            'category_id' => $this->category()->id,
            'message' => 'This message tries to upload a file above the limit.',
            'attachments' => [UploadedFile::fake()->create('huge.txt', 6000, 'text/plain')],
        ]);

        $response->assertSessionHasErrors('attachments.0');
        $this->assertSame(0, Ticket::query()->count());
    }

    public function test_more_than_five_attachments_are_rejected(): void
    {
        $files = [];

        for ($i = 1; $i <= 6; $i++) {
            $files[] = UploadedFile::fake()->create('file-'.$i.'.txt', 1, 'text/plain');
        }

        $response = $this->from('/submit')->post('/submit', [
            'requester_name' => 'Many Files',
            'requester_email' => 'many@example.test',
            'subject' => 'Too many files',
            'category_id' => $this->category()->id,
            'message' => 'This message tries to upload six files at once.',
            'attachments' => $files,
        ]);

        $response->assertSessionHasErrors('attachments');
    }

    public function test_private_attachments_are_only_reachable_through_a_temporary_signed_url(): void
    {
        $ticket = $this->makeTicket();
        $path = 'tickets/'.$ticket->id.'/private-proof.txt';

        Storage::disk('local')->put($path, 'private attachment body');

        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'original_name' => 'private-proof.txt',
            'path' => $path,
            'mime_type' => 'text/plain',
            'size' => 23,
            'source' => 'portal',
        ]);

        try {
            $this->get('/storage/'.$path)->assertForbidden();

            $response = $this->get($attachment->temporaryUrl(10))->assertOk();
            $this->assertStringContainsString('text/plain', (string) $response->headers->get('content-type'));
            $this->assertSame('23', (string) $response->headers->get('content-length'));
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    public function test_the_honeypot_field_blocks_bots(): void
    {
        $response = $this->from('/submit')->post('/submit', [
            'requester_name' => 'Bot',
            'requester_email' => 'bot@example.test',
            'subject' => 'Spam',
            'category_id' => $this->category()->id,
            'message' => 'This is a bot submission that should be rejected.',
            'website' => 'https://spam.example',
        ]);

        $response->assertSessionHasErrors('website');
        $this->assertSame(0, Ticket::query()->count());
    }
}
