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

        // Normalize line breaks (handle both CRLF and CR)
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);

        $this->info('Extracting data structures...');

        // Split file content by the string INSERT INTO (case-insensitive)
        // preg_split returns array on success, we handle empty result below
        $rawChunks = preg_split('/(?=INSERT\s+INTO\s+)/i', $content);

        $statements = [];
        if (is_array($rawChunks)) {
            foreach ($rawChunks as $chunk) {
                $chunk = trim($chunk);
                if (preg_match('/^INSERT\s+INTO/i', $chunk)) {
                    $pos = strpos($chunk, ';');
                    if ($pos !== false) {
                        $statements[] = substr($chunk, 0, $pos + 1);
                    } else {
                        $statements[] = $chunk.';';
                    }
                }
            }
        }

        $totalCount = count($statements);
        $this->info('Found '.$totalCount.' active data tables/blocks to process.');

        if ($totalCount === 0) {
            $this->error('Could not parse rows. Please verify your file contents.');

            return 1;
        }

        $imported = 0;
        $failed = 0;

        $this->output->progressStart($totalCount);

        DB::beginTransaction();
        foreach ($statements as $statement) {
            // Convert backticks to double quotes for SQLite compatibility
            $cleanStatement = str_replace('`', '"', $statement);

            try {
                DB::statement($cleanStatement);
                $imported++;
            } catch (Exception) {
                $failed++;
            }
            $this->output->progressAdvance();
        }
        DB::commit();

        $this->output->progressFinish();
        $this->info('✓ Process complete!');
        $this->info('Successfully executed: '.$imported.' database blocks.');

        if ($failed > 0) {
            $this->warn('Skipped/Failed: '.$failed.' blocks (usually due to structure mismatches).');
        }

        return 0;
    }
}
