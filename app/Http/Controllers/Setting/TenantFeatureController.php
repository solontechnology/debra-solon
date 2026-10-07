<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\TenantFeatureService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantFeatureController extends Controller
{
    public function index(TenantFeatureService $tenantFeatureService)
    {
        abort_unless(auth()->user()->can('setting/features/list'), 403);

        return view('pages.setting.features.index', [
            'features' => $tenantFeatureService->all(),
        ]);
    }

    public function update(
        Request $request,
        string $featureKey,
        TenantFeatureService $tenantFeatureService
    ) {
        abort_unless(auth()->user()->can('setting/features/list'), 403);
        abort_unless(array_key_exists($featureKey, config('tenant_features', [])), 404);

        $validated = $request->validate([
            'enabled' => ['required', Rule::in(['0', '1'])],
        ]);

        $tenantFeatureService->setEnabled($featureKey, $validated['enabled'] === '1');

        return back()->with('success', 'Pengaturan fitur tenant berhasil disimpan.');
    }
}
