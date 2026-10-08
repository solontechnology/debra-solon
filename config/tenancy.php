<?php

use Illuminate\Support\Str;

$baseCacheStore = (string) env('CACHE_STORE', 'database');
$baseCachePrefix = (string) env(
    'CACHE_PREFIX',
    Str::slug((string) env('APP_NAME', 'laravel')).'-cache-'
);
$baseSessionCookie = (string) env(
    'SESSION_COOKIE',
    Str::slug((string) env('APP_NAME', 'laravel'), '_').'_session'
);
$databaseMap = json_decode((string) env('TENANT_DATABASES', '{}'), true, 512, JSON_THROW_ON_ERROR);
if (! is_array($databaseMap)) {
    throw new InvalidArgumentException('TENANT_DATABASES must be a JSON object mapping hostnames to database names.');
}

$normalizedDatabaseMap = [];
$usedDatabases = [];
foreach ($databaseMap as $host => $database) {
    $normalizedHost = strtolower(trim((string) $host));
    $tenantConnection = is_string($database) ? ['database' => $database] : $database;
    $allowedConnectionKeys = ['database', 'host', 'port', 'username', 'password', 'unix_socket'];
    if (! is_array($tenantConnection)
        || array_diff(array_keys($tenantConnection), $allowedConnectionKeys) !== []
        || ! is_string($tenantConnection['database'] ?? null)
        || ! preg_match('/^[A-Za-z0-9_$-]+$/', $tenantConnection['database'])) {
        throw new InvalidArgumentException("Invalid tenant database name configured for host [{$normalizedHost}].");
    }

    foreach (['host', 'username', 'unix_socket'] as $stringKey) {
        if (isset($tenantConnection[$stringKey]) && ! is_string($tenantConnection[$stringKey])) {
            throw new InvalidArgumentException("Invalid database connection setting [{$stringKey}] for tenant host [{$normalizedHost}].");
        }
    }
    if (isset($tenantConnection['password']) && ! is_string($tenantConnection['password'])) {
        throw new InvalidArgumentException("Invalid database password setting for tenant host [{$normalizedHost}].");
    }
    if (isset($tenantConnection['port'])
        && filter_var($tenantConnection['port'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
        throw new InvalidArgumentException("Invalid database port configured for tenant host [{$normalizedHost}].");
    }

    $target = implode('|', [
        strtolower((string) ($tenantConnection['host'] ?? env('DB_HOST', '127.0.0.1'))),
        (string) ($tenantConnection['port'] ?? env('DB_PORT', '3306')),
        strtolower($tenantConnection['database']),
    ]);
    if (isset($usedDatabases[$target])) {
        throw new InvalidArgumentException("Tenant hosts [{$usedDatabases[$target]}] and [{$normalizedHost}] share one database.");
    }

    $normalizedDatabaseMap[$normalizedHost] = is_string($database) ? $database : $tenantConnection;
    $usedDatabases[$target] = $normalizedHost;
}

$defaultHosts = array_filter(array_map(
    static fn (string $host): string => strtolower(trim($host)),
    explode(',', (string) env('TENANCY_DEFAULT_HOSTS', ''))
));
$appHost = parse_url((string) env('APP_URL', ''), PHP_URL_HOST);

if (is_string($appHost) && $appHost !== '') {
    $defaultHosts[] = strtolower($appHost);
}

if (env('APP_ENV') === 'local') {
    $localProjectHost = strtolower(basename(dirname(__DIR__)).'.test');
    $defaultHosts = array_merge($defaultHosts, [
        'localhost',
        '127.0.0.1',
        $localProjectHost,
    ]);
}

$domains = array_keys($normalizedDatabaseMap);

return [
    'databases' => $normalizedDatabaseMap,
    'default_hosts' => array_values(array_unique($defaultHosts)),
    'allow_local_test_hosts' => env('APP_ENV') === 'local',
    'allowed_hosts' => array_values(array_unique(array_merge($domains, $defaultHosts))),
    'base_database_connection' => (string) env('DB_CONNECTION', 'sqlite'),
    'base_cache_store' => $baseCacheStore,
    'base_cache_prefix' => $baseCachePrefix,
    'base_session_cookie' => $baseSessionCookie,
];
