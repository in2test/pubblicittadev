<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Description('Imports data from database/localhost.sql into SQLite')]
#[Signature('db:import-sqlite')]
class ImportSqliteData extends Command
{
    public function handle(): int
    {
        $sqlPath = database_path('localhost.sql');

        if (! file_exists($sqlPath)) {
            $this->error('Error: SQL file not found at: '.$sqlPath);

            return 1;
        }

        $this->info('Reading SQL dump file...');
        $content = (string) file_get_contents($sqlPath);

        $this->info('Extracting data structures...');
        $statements = $this->extractStatements($content);
        $totalCount = count($statements);
        $this->info('Found '.$totalCount.' active data tables/blocks to process.');

        if ($totalCount === 0) {
            $this->error('Could not parse rows. Please verify your file contents.');

            return 1;
        }

        $results = $this->executeStatements($statements);
        $this->reportResults($results['imported'], $results['failed']);

        return 0;
    }

    /**
     * @return array<int, string>
     */
    private function extractStatements(string $content): array
    {
        $normalizedContent = str_replace(["\r\n", "\r"], "\n", $content);
        $rawChunks = preg_split('/(?=INSERT\s+INTO\s+)/i', $normalizedContent) ?: [];
        $statements = [];

        foreach ($rawChunks as $chunk) {
            $chunk = trim($chunk);
            if (! preg_match('/^INSERT\s+INTO/i', $chunk)) {
                continue;
            }

            $position = strpos($chunk, ';');
            $statements[] = $position !== false ? substr($chunk, 0, $position + 1) : $chunk.';';
        }

        return $statements;
    }

    /**
     * @param  array<int, string>  $statements
     * @return array{imported: int, failed: int}
     */
    private function executeStatements(array $statements): array
    {
        $imported = 0;
        $failed = 0;

        $this->output->progressStart(count($statements));
        DB::beginTransaction();

        foreach ($statements as $index => $statement) {
            try {
                DB::statement(str_replace('`', '"', $statement));
                $imported++;
            } catch (Exception $exception) {
                $failed++;
                $this->warn('Skipped SQL block '.($index + 1).': '.class_basename($exception));
            }

            $this->output->progressAdvance();
        }

        DB::commit();
        $this->output->progressFinish();

        return ['imported' => $imported, 'failed' => $failed];
    }

    private function reportResults(int $imported, int $failed): void
    {
        $this->info('✓ Process complete!');
        $this->info('Successfully executed: '.$imported.' database blocks.');

        if ($failed > 0) {
            $this->warn('Skipped/Failed: '.$failed.' blocks (usually due to structure mismatches).');
        }
    }
}
