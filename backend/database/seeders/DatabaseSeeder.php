<?php

namespace Database\Seeders;

use App\Services\FormImporter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with the form definition from the JSON feed.
     */
    public function run(FormImporter $importer): void
    {
        $importer->importFromFile(database_path('data/submission.json'));
    }
}
