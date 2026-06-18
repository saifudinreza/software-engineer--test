<?php

namespace App\Console\Commands;

use App\Services\FormImporter;
use Illuminate\Console\Command;

class ImportForm extends Command
{
    protected $signature = 'forms:import {file? : Path to the JSON feed (defaults to database/data/submission.json)}';

    protected $description = 'Import the form definition from the JSON feed into the database';

    public function handle(FormImporter $importer): int
    {
        $path = $this->argument('file') ?: database_path('data/submission.json');

        $this->info("Importing form definition from: {$path}");

        try {
            $counts = $importer->importFromFile($path);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Imported %d sections, %d fields, %d options.',
            $counts['sections'],
            $counts['fields'],
            $counts['options'],
        ));

        return self::SUCCESS;
    }
}
