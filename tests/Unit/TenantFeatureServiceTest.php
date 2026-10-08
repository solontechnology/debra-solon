<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Tenancy\TenantFeatureService;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TenantFeatureServiceTest extends TestCase
{
    public function test_super_admin_can_access_menu_even_when_tenant_menu_flag_is_disabled(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('hasRole')
            ->with('super admin')
            ->willReturn(true);
        Auth::setUser($user);

        request()->attributes->set('tenant.features.enabled', ['menu_job_divisi' => false]);

        $this->assertTrue(app(TenantFeatureService::class)->isEnabled('menu_job_divisi'));
    }

    public function test_super_admin_does_not_bypass_non_menu_feature_flags(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->never())->method('hasRole');
        Auth::setUser($user);

        request()->attributes->set('tenant.features.enabled', ['configurable_approval' => false]);

        $this->assertFalse(app(TenantFeatureService::class)->isEnabled('configurable_approval'));
    }
}
