<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConfigurationUrlParser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MigrateTenantDatabases extends Command
{
    protected $signature = 'tenants:migrate';

    protected $description = 'Run pending migrations for every configured tenant database';

    public function handle(): int
    {
        $tenants = config('tenancy.databases', []);
        if ($tenants === []) {
            $this->warn('No tenant databases are configured in TENANT_DATABASES.');

            return self::SUCCESS;
        }

        $baseConnection = config('database.connections.'.config('tenancy.base_database_connection'));
        $migrated = 0;

        foreach ($tenants as $host => $database) {
            $tenantConnection = is_string($database) ? ['database' => $database] : $database;
            if (! is_array($tenantConnection)
                || ! is_string($tenantConnection['database'] ?? null)
                || ! preg_match('/^[A-Za-z0-9_$-]+$/', $tenantConnection['database'])) {
                throw new InvalidArgumentException("Invalid tenant database name configured for host [{$host}].");
            }

            $connection = (new ConfigurationUrlParser)->parseConfiguration($baseConnection);
            foreach ($tenantConnection as $key => $value) {
                $connection[$key] = $value;
            }
            $connection['database'] = $tenantConnection['database'];
            $connection['url'] = null;
            config(['database.connections.tenant_migration' => $connection]);
            DB::purge('tenant_migration');

            $this->line("Migrating tenant [{$host}] ({$tenantConnection['database']})...");
            $exitCode = Artisan::call('migrate', [
                '--database' => 'tenant_migration',
                '--force' => true,
            ]);
            $this->output->write(Artisan::output());

            if ($exitCode !== self::SUCCESS) {
                return $exitCode;
            }

            $migrated++;
        }

        $this->info("Tenant migrations completed for {$migrated} database(s).");

        return self::SUCCESS;
    }
}
