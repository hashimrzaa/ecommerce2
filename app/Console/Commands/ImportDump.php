<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Loads a .sql export into the database named in .env — used once to copy the local
 * store to a hosted database. It goes through PHP, so it works with hosts that need an
 * encrypted connection (database/ca.pem) without any extra MySQL tools.
 */
class ImportDump extends Command
{
    protected $signature = 'store:import {file=database/dumps/eclothes-live.sql} {--force : Do not ask for confirmation}';

    protected $description = 'Import a .sql export into the database configured in .env (replaces existing tables)';

    public function handle(): int
    {
        $file = base_path($this->argument('file'));
        if (! is_file($file)) {
            $this->error("File not found: {$this->argument('file')}");

            return self::FAILURE;
        }

        $host = config('database.connections.mysql.host');
        $database = config('database.connections.mysql.database');
        $this->line("Target database: <info>$database</info> on <info>$host</info>");

        if (in_array($host, ['127.0.0.1', 'localhost'], true)) {
            $this->warn('This is your LOCAL database. Put the hosted database details in .env first if you meant to fill the live one.');
        }
        try {
            $tables = count(DB::select('SHOW TABLES'));
        } catch (\Throwable $e) {
            $this->error('Could not connect: '.$e->getMessage());
            $this->line('Check DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD in .env, and that database/ca.pem is saved.');

            return self::FAILURE;
        }

        $question = $tables
            ? "This database already has $tables tables. Importing REPLACES them and their data. Continue?"
            : 'The database is empty. Import the file now?';
        if (! $this->option('force') && ! $this->confirm($question, $tables === 0)) {
            $this->line('Nothing was changed.');

            return self::SUCCESS;
        }

        $this->line('Importing… this can take a minute.');
        DB::unprepared(file_get_contents($file));

        $this->info('Done. '.count(DB::select('SHOW TABLES')).' tables, '.DB::table('products')->count().' products, '.DB::table('orders')->count().' orders.');

        return self::SUCCESS;
    }
}
