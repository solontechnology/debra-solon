<?php

namespace Tests\Unit;

use App\Http\Middleware\ResolveTenantDatabase;
use App\Services\Tenancy\TenantDatabaseResolver;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TenantDatabaseResolverTest extends TestCase
{
    public function test_it_resolves_only_exactly_configured_tenant_hosts(): void
    {
        config([
            'tenancy.databases' => [
                'client-a.example.test' => 'solon_client_a',
                'client-b.example.test' => [
                    'database' => 'solon_client_b',
                    'username' => 'client_b_app',
                    'password' => 'tenant-password',
                ],
            ],
            'tenancy.default_hosts' => ['localhost'],
        ]);

        $resolver = new TenantDatabaseResolver;
        $clientA = $resolver->resolve('client-a.example.test');
        $clientB = $resolver->resolve('client-b.example.test');

        $this->assertSame('solon_client_a', $clientA['database']);
        $this->assertSame('solon_client_b', $clientB['database']);
        $this->assertSame('client_b_app', $clientB['connection']['username']);
        $this->assertSame('tenant-password', $clientB['connection']['password']);
        $this->assertNotSame($clientA['tenant_key'], $clientB['tenant_key']);
        $this->assertNull($resolver->resolve('unknown.example.test'));
    }

    public function test_it_preserves_an_explicit_default_host_for_the_legacy_database(): void
    {
        config([
            'tenancy.databases' => [],
            'tenancy.default_hosts' => ['localhost'],
        ]);

        $tenant = (new TenantDatabaseResolver)->resolve('localhost');

        $this->assertTrue($tenant['is_default']);
        $this->assertNull($tenant['database']);
        $this->assertNull((new TenantDatabaseResolver)->resolve('unregistered.example.test'));
    }

    public function test_middleware_switches_database_and_isolates_tenant_assets(): void
    {
        config([
            'tenancy.databases' => ['client-a.example.test' => 'solon_client_a'],
            'tenancy.default_hosts' => ['localhost'],
            'tenancy.base_database_connection' => 'sqlite',
            'tenancy.base_cache_store' => 'array',
            'tenancy.base_cache_prefix' => 'tests-cache-',
            'tenancy.base_session_cookie' => 'tests_session',
        ]);

        $request = Request::create('https://client-a.example.test/login');
        $middleware = new ResolveTenantDatabase(new TenantDatabaseResolver);
        $tenantCookie = null;
        $response = $middleware->handle($request, function (Request $request) {
            $this->assertSame('tenant_runtime', config('database.default'));
            $this->assertSame('solon_client_a', config('database.connections.tenant_runtime.database'));
            $this->assertSame('solon_client_a', $request->attributes->get('tenant.context')['database']);
            $this->assertSame($request->attributes->get('tenant.context')['tenant_key'], $request->attributes->get('tenant.storage_prefix'));
            $this->assertStringContainsString($request->attributes->get('tenant.storage_prefix'), config('filesystems.disks.public.root'));

            return new Response('', 200);
        });
        $tenantCookie = config('session.cookie');

        $this->assertSame(200, $response->getStatusCode());

        $defaultRequest = Request::create('http://localhost/');
        $defaultResponse = $middleware->handle($defaultRequest, function (Request $request) use ($tenantCookie) {
            $this->assertSame('sqlite', config('database.default'));
            $this->assertTrue($request->attributes->get('tenant.context')['is_default']);
            $this->assertNotSame($tenantCookie, config('session.cookie'));
            $this->assertSame(storage_path('app/public'), config('filesystems.disks.public.root'));

            return new Response('', 200);
        });
        $this->assertSame(200, $defaultResponse->getStatusCode());
    }
}
