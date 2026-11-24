<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AttachmentService
{
    public function storeUploaded(
        Ticket $ticket,
        ?TicketMessage $message,
        array $files,
        string $source = 'portal',
        ?User $uploadedBy = null,
    ): array {
        $stored = [];
        $isDemo = (bool) $uploadedBy?->isDemo();

        if ($isDemo) {
            $files = array_slice($files, 0, (int) config('helpdesk.demo_attachments.max_files', 2));
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $original = $this->safeName($file->getClientOriginalName());
            $extension = strtolower($file->getClientOriginalExtension());

            $this->assertAllowed($extension, $file->getSize(), $isDemo);

            $stored[] = $this->persist(
                $ticket,
                $message,
                $original,
                $extension,
                $file->getMimeType() ?: 'application/octet-stream',
                $file->getSize(),
                fn (string $path) => Storage::disk($this->disk())->putFileAs(dirname($path), $file, basename($path)),
                $source,
                $uploadedBy,
            );
        }

        return $stored;
    }

    public function storeContents(
        Ticket $ticket,
        ?TicketMessage $message,
        string $filename,
        ?string $mimeType,
        string $contents,
        string $source = 'email',
        ?User $uploadedBy = null,
    ): ?TicketAttachment {
        $original = $this->safeName($filename);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $size = strlen($contents);

        try {
            $this->assertAllowed($extension, $size);
        } catch (InvalidArgumentException) {
            return null;
        }

        return $this->persist(
            $ticket,
            $message,
            $original,
            $extension,
            $mimeType ?: 'application/octet-stream',
            $size,
            fn (string $path) => Storage::disk($this->disk())->put($path, $contents),
            $source,
            $uploadedBy,
        );
    }

    protected function persist(
        Ticket $ticket,
        ?TicketMessage $message,
        string $original,
        string $extension,
        string $mimeType,
        int $size,
        callable $writer,
        string $source,
        ?User $uploadedBy,
    ): TicketAttachment {
        $path = trim(config('helpdesk.attachments.directory'), '/').'/'.$ticket->id.'/'.Str::uuid()->toString().'.'.$extension;

        $writer($path);

        return TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'ticket_message_id' => $message?->id,
            'uploaded_by' => $uploadedBy?->id,
            'original_name' => $original,
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => $size,
            'source' => $source,
        ]);
    }

    protected function assertAllowed(string $extension, int $size, bool $isDemo = false): void
    {
        $allowed = config('helpdesk.attachments.extensions', []);
        $maxKb = $isDemo
            ? (int) config('helpdesk.demo_attachments.max_kilobytes', 2048)
            : (int) config('helpdesk.attachments.max_kilobytes', 5120);
        $maxBytes = $maxKb * 1024;

        if (! in_array($extension, $allowed, true)) {
            throw new InvalidArgumentException('Attachment type is not allowed: '.$extension);
        }

        if ($size <= 0 || $size > $maxBytes) {
            throw new InvalidArgumentException('Attachment size is not allowed.');
        }
    }

    protected function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?: 'attachment';
        $name = trim($name);

        return Str::limit($name === '' ? 'attachment' : $name, 120, '');
    }

    protected function disk(): string
    {
        return (string) config('helpdesk.attachments.disk', 'local');
    }
}
