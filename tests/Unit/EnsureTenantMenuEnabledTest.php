<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureTenantMenuEnabled;
use App\Services\Tenancy\TenantFeatureService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EnsureTenantMenuEnabledTest extends TestCase
{
    public function test_it_blocks_routes_when_the_tenant_menu_is_disabled(): void
    {
        $features = $this->createMock(TenantFeatureService::class);
        $features->expects($this->once())
            ->method('isEnabled')
            ->with('menu_job_divisi')
            ->willReturn(false);

        $middleware = new EnsureTenantMenuEnabled($features);

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle(Request::create('/job/divisi'), fn () => new Response);
    }

    public function test_it_keeps_tenant_feature_settings_accessible_when_settings_menu_is_disabled(): void
    {
        $features = $this->createMock(TenantFeatureService::class);
        $features->expects($this->never())->method('isEnabled');

        $middleware = new EnsureTenantMenuEnabled($features);
        $response = $middleware->handle(
            Request::create('/setting/features'),
            fn () => new Response('', 200)
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_uses_the_selected_archive_submenu_for_shared_routes(): void
    {
        $features = $this->createMock(TenantFeatureService::class);
        $features->expects($this->once())
            ->method('isEnabled')
            ->with('menu_arsip_ppat')
            ->willReturn(false);

        $middleware = new EnsureTenantMenuEnabled($features);

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle(
            Request::create('/arsip/bundle?tipe=ppat'),
            fn () => new Response
        );
    }

    public function test_it_blocks_number_entry_when_its_category_submenu_is_disabled(): void
    {
        $features = $this->createMock(TenantFeatureService::class);
        $features->expects($this->once())
            ->method('isEnabled')
            ->with('menu_laporan_nomor_ppat')
            ->willReturn(false);

        $middleware = new EnsureTenantMenuEnabled($features);

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle(
            Request::create('/inputNomorRekanan', 'POST', ['kategori' => 'ppat']),
            fn () => new Response
        );
    }

    public function test_it_does_not_reject_a_shared_route_when_no_submenu_rule_matches(): void
    {
        $features = $this->createMock(TenantFeatureService::class);
        $features->expects($this->never())->method('isEnabled');

        $middleware = new EnsureTenantMenuEnabled($features);
        $response = $middleware->handle(
            Request::create('/finance/job-divisi'),
            fn () => new Response('', 200)
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_allows_the_enabled_finance_submenu_for_its_query_type(): void
    {
        $features = $this->createMock(TenantFeatureService::class);
        $features->expects($this->once())
            ->method('isEnabled')
            ->with('menu_finance_in')
            ->willReturn(true);

        $middleware = new EnsureTenantMenuEnabled($features);
        $response = $middleware->handle(
            Request::create('/finance/job-divisi?type=in'),
            fn () => new Response('', 200)
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_uses_the_selected_job_submenu_for_export_routes(): void
    {
        $features = $this->createMock(TenantFeatureService::class);
        $features->expects($this->once())
            ->method('isEnabled')
            ->with('menu_job_pnbp')
            ->willReturn(false);

        $middleware = new EnsureTenantMenuEnabled($features);

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle(
            Request::create('/job/export-data?type=pnbp'),
            fn () => new Response
        );
    }

    public function test_it_blocks_archive_creation_for_a_disabled_category(): void
    {
        $features = $this->createMock(TenantFeatureService::class);
        $features->expects($this->once())
            ->method('isEnabled')
            ->with('menu_arsip_ppat')
            ->willReturn(false);

        $middleware = new EnsureTenantMenuEnabled($features);

        $this->expectException(NotFoundHttpException::class);
        $middleware->handle(
            Request::create('/arsip/bundle', 'POST', ['kategori' => 'ppat']),
            fn () => new Response
        );
    }

    public function test_it_does_not_block_unmapped_routes_under_a_menu_prefix(): void
    {
        $features = $this->createMock(TenantFeatureService::class);
        $features->expects($this->never())->method('isEnabled');

        $middleware = new EnsureTenantMenuEnabled($features);
        $response = $middleware->handle(
            Request::create('/job/akta/filter-data'),
            fn () => new Response('', 200)
        );

        $this->assertSame(200, $response->getStatusCode());
    }
}
