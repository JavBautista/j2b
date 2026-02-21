<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SatImportCatalogs extends Command
{
    protected $signature = 'sat:import';
    protected $description = 'Import SAT catalogs (product codes and unit codes) from CSV files';

    public function handle(): int
    {
        $this->importProductCodes();
        $this->importUnitCodes();

        $this->info('SAT catalogs imported successfully.');
        return 0;
    }

    private function importProductCodes(): void
    {
        $file = database_path('data/sat_product_codes.csv');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return;
        }

        $this->info('Importing SAT product codes...');
        DB::table('sat_product_codes')->truncate();

        $handle = fopen($file, 'r');
        $batch = [];
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2 || empty($row[0])) continue;

            $batch[] = [
                'code' => trim($row[0]),
                'description' => trim($row[1]),
            ];

            if (count($batch) >= 1000) {
                DB::table('sat_product_codes')->insert($batch);
                $count += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('sat_product_codes')->insert($batch);
            $count += count($batch);
        }

        fclose($handle);
        $this->info("  → {$count} product codes imported.");
    }

    private function importUnitCodes(): void
    {
        $file = database_path('data/sat_unit_codes.csv');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return;
        }

        $this->info('Importing SAT unit codes...');
        DB::table('sat_unit_codes')->truncate();

        $handle = fopen($file, 'r');
        $batch = [];
        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2 || empty($row[0])) continue;

            $batch[] = [
                'code' => trim($row[0]),
                'name' => trim($row[1]),
                'note' => isset($row[2]) ? mb_substr(trim($row[2]), 0, 500) : null,
            ];

            if (count($batch) >= 500) {
                DB::table('sat_unit_codes')->insert($batch);
                $count += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('sat_unit_codes')->insert($batch);
            $count += count($batch);
        }

        fclose($handle);
        $this->info("  → {$count} unit codes imported.");
    }
}
