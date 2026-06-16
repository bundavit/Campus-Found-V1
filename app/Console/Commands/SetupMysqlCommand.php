<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PDO;
use Throwable;

class SetupMysqlCommand extends Command
{
    protected $signature = 'lostfound:setup-mysql
        {--database=contactappdb : Database name to create and use}
        {--username=laravel : MySQL username}
        {--password= : MySQL password. If omitted, you will be prompted.}';

    protected $description = 'Create contactappdb (laravel user) and migrate Lost-Found to MySQL';

    public function handle(): int
    {
        $database = (string) $this->option('database');
        $username = (string) $this->option('username');
        $password = (string) ($this->option('password') ?: $this->secret('MySQL password'));

        try {
            $pdo = new PDO('mysql:host=127.0.0.1;port=3306', $username, $password);
            $pdo->exec(
                sprintf(
                    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                    str_replace('`', '``', $database)
                )
            );
            $this->info("Database {$database} is ready.");
        } catch (Throwable $e) {
            $this->error("Could not connect as {$username}: ".$e->getMessage());
            $this->line('Create a MySQL user/database first, then rerun this command with your own credentials.');

            return self::FAILURE;
        }

        $this->updateEnvForMysql($database, $username, $password);
        Artisan::call('config:clear');
        $this->info("Updated .env for MySQL ({$database}).");

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        if ($this->call('lostfound:check-db') === self::SUCCESS) {
            $this->newLine();
            $this->info('Lost-Found now uses MySQL.');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    private function updateEnvForMysql(string $database, string $username, string $password): void
    {
        $envPath = base_path('.env');
        $env = File::get($envPath);

        $mysqlBlock = <<<ENV
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE={$database}
DB_USERNAME={$username}
DB_PASSWORD={$password}
ENV;

        if (preg_match('/DB_CONNECTION=sqlite/s', $env)) {
            $env = preg_replace(
                '/DB_CONNECTION=sqlite\n(?:#.*\n|DB_.*\n)*/',
                $mysqlBlock."\n",
                $env
            );
        } else {
            $env = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=mysql', $env);
            $env = preg_replace('/DB_DATABASE=.*/', "DB_DATABASE={$database}", $env);
            $env = preg_replace('/DB_USERNAME=.*/', "DB_USERNAME={$username}", $env);
            $env = preg_replace('/DB_PASSWORD=.*/', "DB_PASSWORD={$password}", $env);
        }

        File::put($envPath, $env);
    }
}
