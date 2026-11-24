<?php

namespace App\Support;

use Carbon\Carbon;
use Throwable;

/**
 * A small, dependency-free MIME e-mail parser used by the inbox simulation.
 * It supports multipart messages, base64/quoted-printable bodies, RFC 2047
 * encoded headers, charsets and file attachments.
 */
class EmlParser
{
    /**
     * @return array{
     *     from_email:?string, from_name:?string, subject:string, text:string, html:?string,
     *     message_id:?string, in_reply_to:?string, references:array<int,string>,
     *     date:?Carbon, attachments:array<int, array{filename:string, mime_type:string, content:string}>
     * }
     */
    public function parse(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        [$headerBlock, $body] = array_pad(explode("\n\n", $raw, 2), 2, '');
        $headers = $this->parseHeaders($headerBlock);

        $collected = ['text' => [], 'html' => [], 'attachments' => []];
        $this->collectPart($headers, $body, $collected, 0);

        $text = trim(implode("\n\n", $collected['text']));
        $html = trim(implode("\n", $collected['html'])) ?: null;

        if ($text === '' && $html !== null) {
            $text = $this->htmlToText($html);
        }

        $from = $this->parseAddress($this->firstHeader($headers, 'from'));

        return [
            'from_email' => $from['email'],
            'from_name' => $from['name'],
            'subject' => $this->decodeHeader($this->firstHeader($headers, 'subject')),
            'text' => $text,
            'html' => $html,
            'message_id' => $this->normalizeMessageId($this->firstHeader($headers, 'message-id')),
            'in_reply_to' => $this->normalizeMessageId($this->firstHeader($headers, 'in-reply-to')),
            'references' => $this->referenceList($this->firstHeader($headers, 'references')),
            'date' => $this->parseDate($this->firstHeader($headers, 'date')),
            'attachments' => $collected['attachments'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function parseHeaders(string $block): array
    {
        $headers = [];
        $current = null;

        foreach (explode("\n", $block) as $line) {
            if ($line === '') {
                continue;
            }

            if (preg_match('/^[ \t]/', $line) && $current !== null) {
                $last = array_key_last($headers[$current]);
                $headers[$current][$last] .= ' '.trim($line);

                continue;
            }

            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $current = strtolower(trim($name));
                $headers[$current][] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @param  array{text:array<int,string>, html:array<int,string>, attachments:array<int, array{filename:string, mime_type:string, content:string}>}  $collected
     */
    protected function collectPart(array $headers, string $body, array &$collected, int $depth): void
    {
        if ($depth > 10) {
            return;
        }

        [$type, $params] = $this->parseStructuredHeader($this->firstHeader($headers, 'content-type') ?: 'text/plain');

        if (str_starts_with($type, 'multipart/') && ! empty($params['boundary'])) {
            foreach ($this->splitMultipart($body, $params['boundary']) as $chunk) {
                [$partHeaderBlock, $partBody] = array_pad(explode("\n\n", $chunk, 2), 2, '');
                $this->collectPart($this->parseHeaders($partHeaderBlock), $partBody, $collected, $depth + 1);
            }

            return;
        }

        [$disposition, $dispositionParams] = $this->parseStructuredHeader($this->firstHeader($headers, 'content-disposition') ?: '');
        $filename = $dispositionParams['filename'] ?? $params['name'] ?? null;
        $filename = $filename ? $this->decodeHeader($filename) : null;

        $decoded = $this->decodeBody($body, $this->firstHeader($headers, 'content-transfer-encoding'), $params['charset'] ?? null);

        if ($disposition === 'attachment' || ($filename !== null && $type !== 'text/plain')) {
            $collected['attachments'][] = [
                'filename' => $filename ?: 'attachment-'.(count($collected['attachments']) + 1).'.'.$this->extensionFor($type),
                'mime_type' => $type,
                'content' => $decoded,
            ];

            return;
        }

        if ($type === 'text/plain') {
            $collected['text'][] = $decoded;

            return;
        }

        if ($type === 'text/html') {
            $collected['html'][] = $decoded;

            return;
        }

        if ($type !== '' && $type !== 'text/plain') {
            $collected['attachments'][] = [
                'filename' => $filename ?: 'part-'.(count($collected['attachments']) + 1).'.'.$this->extensionFor($type),
                'mime_type' => $type,
                'content' => $decoded,
            ];
        }
    }

    /**
     * @return array<int, string>
     */
    protected function splitMultipart(string $body, string $boundary): array
    {
        $chunks = explode('--'.$boundary, $body);
        $parts = [];

        foreach (array_slice($chunks, 1) as $chunk) {
            if (str_starts_with($chunk, '--')) {
                break;
            }

            $chunk = ltrim($chunk, "\n");

            if (str_ends_with($chunk, "\n")) {
                $chunk = substr($chunk, 0, -1);
            }

            if (trim($chunk) !== '') {
                $parts[] = $chunk;
            }
        }

        return $parts;
    }

    protected function decodeBody(string $body, ?string $encoding, ?string $charset): string
    {
        $encoding = strtolower(trim((string) $encoding));

        $decoded = match ($encoding) {
            'base64' => (string) base64_decode(preg_replace('/\s+/', '', $body) ?? '', true),
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };

        $charset = strtolower(trim((string) $charset));

        if ($charset !== '' && ! in_array($charset, ['utf-8', 'utf8', 'us-ascii', 'ascii'], true)) {
            try {
                $decoded = mb_convert_encoding($decoded, 'UTF-8', $charset);
            } catch (Throwable) {
                // Keep the bytes as-is when the declared charset is unknown.
            }
        }

        return $decoded;
    }

    /**
     * @return array{0:string, 1:array<string,string>}
     */
    protected function parseStructuredHeader(string $value): array
    {
        if (trim($value) === '') {
            return ['', []];
        }

        $segments = preg_split('/;(?=(?:[^"]*"[^"]*")*[^"]*$)/', $value) ?: [$value];
        $main = strtolower(trim(array_shift($segments) ?? ''));
        $params = [];

        foreach ($segments as $segment) {
            if (! str_contains($segment, '=')) {
                continue;
            }

            [$key, $paramValue] = explode('=', $segment, 2);
            $key = strtolower(trim($key));
            $paramValue = trim($paramValue);
            $paramValue = trim($paramValue, '"');

            if (str_ends_with($key, '*')) {
                $key = substr($key, 0, -1);
                $paramValue = rawurldecode(preg_replace("/^[^']*'[^']*'/", '', $paramValue) ?? $paramValue);
            }

            $params[$key] = $paramValue;
        }

        return [$main, $params];
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     */
    protected function firstHeader(array $headers, string $name): string
    {
        return $headers[$name][0] ?? '';
    }

    protected function decodeHeader(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (! str_contains($value, '=?')) {
            return trim($value);
        }

        $decoded = @mb_decode_mimeheader($value);

        return trim($decoded !== false ? $decoded : $value);
    }

    /**
     * @return array{name:?string, email:?string}
     */
    protected function parseAddress(string $value): array
    {
        $value = $this->decodeHeader($value);

        if ($value === '') {
            return ['name' => null, 'email' => null];
        }

        if (preg_match('/^(.*)<([^>]+)>\s*$/u', $value, $matches)) {
            $name = trim(trim($matches[1]), " \t\"'");
            $email = trim($matches[2]);
        } else {
            $name = null;
            $email = trim($value, " \t<>\"'");
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $name = null;
            $email = null;
        }

        return ['name' => $name !== '' ? $name : null, 'email' => $email];
    }

    protected function normalizeMessageId(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $value) ?: [];
        $first = trim($parts[0] ?? '', '<> ');

        return $first === '' ? null : $first;
    }

    /**
     * @return array<int, string>
     */
    protected function referenceList(string $value): array
    {
        preg_match_all('/<([^>]+)>/', $value, $matches);

        return array_values(array_filter(array_map('trim', $matches[1] ?? [])));
    }

    protected function parseDate(string $value): ?Carbon
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function htmlToText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\/(p|div|li|h[1-6]|tr)>/i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    protected function extensionFor(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'text/csv' => 'csv',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            default => 'bin',
        };
    }
}
