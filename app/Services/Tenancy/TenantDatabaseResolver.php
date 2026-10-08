<?php

namespace App\Services\Tenancy;

use InvalidArgumentException;

class TenantDatabaseResolver
{
    public function resolve(string $host): ?array
    {
        $host = strtolower(trim($host));
        $databaseMap = config('tenancy.databases', []);

        foreach ($databaseMap as $domain => $database) {
            if (strtolower(trim((string) $domain)) !== $host) {
                continue;
            }

            $tenantConnection = is_string($database) ? ['database' => $database] : $database;
            if (! is_array($tenantConnection)
                || ! is_string($tenantConnection['database'] ?? null)
                || ! preg_match('/^[A-Za-z0-9_$-]+$/', $tenantConnection['database'])) {
                throw new InvalidArgumentException("Invalid database name configured for tenant host [{$host}].");
            }

            return [
                'host' => $host,
                'database' => $tenantConnection['database'],
                'connection' => array_diff_key($tenantConnection, ['database' => true]),
                'tenant_key' => substr(hash('sha256', $host.'|'.$tenantConnection['database']), 0, 16),
                'is_default' => false,
            ];
        }

        if (in_array($host, config('tenancy.default_hosts', []), true)) {
            return [
                'host' => $host,
                'database' => null,
                'tenant_key' => substr(hash(
                    'sha256',
                    $host.'|'.config(
                        'database.connections.'.config('tenancy.base_database_connection').'.database'
                    )
                ), 0, 16),
                'is_default' => true,
            ];
        }

        if (config('tenancy.allow_local_test_hosts', false)
            && preg_match('/^(?:[a-z0-9-]+\.)+test$/', $host)) {
            return [
                'host' => $host,
                'database' => null,
                'tenant_key' => substr(hash(
                    'sha256',
                    $host.'|'.config(
                        'database.connections.'.config('tenancy.base_database_connection').'.database'
                    )
                ), 0, 16),
                'is_default' => true,
            ];
        }

        return null;
    }
}
