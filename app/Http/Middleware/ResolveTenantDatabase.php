<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantDatabaseResolver;
use Closure;
use Illuminate\Database\ConfigurationUrlParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantDatabase
{
    public function __construct(private TenantDatabaseResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolver->resolve($request->getHost());

        abort_unless(
            $tenant,
            404,
            "Domain [{$request->getHost()}] belum terdaftar. Tambahkan domain tenant ke TENANT_DATABASES atau TENANCY_DEFAULT_HOSTS."
        );

        $baseConnectionName = config('tenancy.base_database_connection');
        $baseConnection = config("database.connections.{$baseConnectionName}");
        $tenantConnectionName = 'tenant_runtime';

        if ($tenant['is_default']) {
            config(['database.default' => $baseConnectionName]);
            DB::purge($tenantConnectionName);
        } else {
            $connection = (new ConfigurationUrlParser)->parseConfiguration($baseConnection);
            foreach ($tenant['connection'] as $key => $value) {
                $connection[$key] = $value;
            }
            $connection['database'] = $tenant['database'];
            $connection['url'] = null;

            config([
                "database.connections.{$tenantConnectionName}" => $connection,
                'database.default' => $tenantConnectionName,
            ]);
            DB::purge($tenantConnectionName);
        }

        $cacheConnectionName = $tenant['is_default'] ? $baseConnectionName : $tenantConnectionName;
        $this->configureCache($tenant['tenant_key'], $cacheConnectionName);
        $this->configureSessionCookie($tenant['tenant_key']);
        $this->configurePublicStorage($request, $tenant['tenant_key'], $tenant['is_default']);

        $request->attributes->set('tenant.context', $tenant);
        $request->attributes->set('tenant.storage_prefix', $tenant['is_default'] ? null : $tenant['tenant_key']);

        return $next($request);
    }

    private function configureCache(string $tenantKey, string $connectionName): void
    {
        $baseStoreName = config('tenancy.base_cache_store');
        $storeName = 'tenant_runtime';
        $store = config("cache.stores.{$baseStoreName}");

        if (! is_array($store)) {
            return;
        }

        if (($store['driver'] ?? null) === 'database') {
            $store['connection'] = $connectionName;
        }

        if (($store['driver'] ?? null) === 'file') {
            $store['path'] = storage_path('framework/cache/data/tenants/'.$tenantKey);
            $store['lock_path'] = $store['path'];
        }

        config([
            "cache.stores.{$storeName}" => $store,
            'cache.default' => $storeName,
            'cache.prefix' => rtrim((string) config('tenancy.base_cache_prefix'), '-').'-'.$tenantKey.'-',
        ]);
        app('cache')->forgetDriver($storeName);
    }

    private function configureSessionCookie(string $tenantKey): void
    {
        $cookie = Str::slug((string) config('tenancy.base_session_cookie'), '_');

        config([
            'session.cookie' => substr($cookie.'_'.$tenantKey, 0, 120),
            'session.domain' => null,
        ]);
        app('session')->forgetDrivers();
    }

    private function configurePublicStorage(Request $request, string $tenantKey, bool $isDefault): void
    {
        $disk = config('filesystems.disks.public');
        if (($disk['driver'] ?? null) !== 'local') {
            return;
        }

        $disk['root'] = $isDefault
            ? storage_path('app/public')
            : storage_path('app/public/tenants/'.$tenantKey);
        $disk['url'] = $isDefault
            ? $request->getSchemeAndHttpHost().'/storage'
            : $request->getSchemeAndHttpHost().'/storage/tenants/'.$tenantKey;

        config(['filesystems.disks.public' => $disk]);
        Storage::forgetDisk('public');
    }
}
