<?php

namespace App\Console\Commands;

use App\Services\EmailInboxImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportInboxCommand extends Command
{
    protected $signature = 'helpdesk:import-inbox
        {--path= : Directory containing .eml files (defaults to storage/app/inbox)}
        {--file= : Import a single .eml file from the inbox directory}
        {--dry-run : Parse files without writing to the database}';

    protected $description = 'Import .eml fixtures from the simulated support inbox into tickets and replies';

    public function handle(EmailInboxImporter $importer): int
    {
        $directory = $this->option('path') ?: storage_path('app/'.config('helpdesk.inbox_path', 'inbox'));
        $dryRun = (bool) $this->option('dry-run');

        File::ensureDirectoryExists($directory);

        $summary = $this->option('file')
            ? $importer->importFile($directory.DIRECTORY_SEPARATOR.basename((string) $this->option('file')), $dryRun)
            : $importer->importDirectory($directory, $dryRun);

        if ($summary['rows'] === []) {
            $this->warn(__('app.email.no_files', ['path' => $directory]));

            return self::SUCCESS;
        }

        $this->table(
            [__('app.email.file'), __('app.email.result'), __('app.email.detail')],
            array_map(fn (array $row) => [$row['file'], $row['status'], $row['detail']], $summary['rows']),
        );

        $this->info($dryRun
            ? __('app.email.dry_summary', $this->summaryLabels($summary))
            : __('app.email.summary', $this->summaryLabels($summary)));

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, int>
     */
    protected function summaryLabels(array $summary): array
    {
        return [
            'new' => $summary['new'],
            'replies' => $summary['replies'],
            'duplicates' => $summary['duplicates'],
            'rejected' => $summary['rejected'],
            'failed' => $summary['failed'],
        ];
    }
}
