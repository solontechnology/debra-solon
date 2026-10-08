<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Approval\ApprovalWorkflowService;
use App\Services\Tenancy\TenantFeatureService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class ApprovalConfigurationController extends Controller
{
    private const WORKFLOWS = [
        'freeze' => 'Berkas Bermasalah - Freeze',
        'penambahan_item' => 'Job - Penambahan Item',
        'pembatalan_item' => 'Job - Pembatalan Item',
        'finance' => 'Finance - Persetujuan Biaya',
    ];

    public function index(
        ApprovalWorkflowService $approvalWorkflowService,
        TenantFeatureService $tenantFeatureService
    ) {
        abort_unless(auth()->user()->can('setting/approval/list'), 403);
        abort_unless($tenantFeatureService->isEnabled('configurable_approval'), 404);

        $configurations = collect(self::WORKFLOWS)->mapWithKeys(
            fn ($label, $key) => [$key => [
                'label' => $label,
                ...$approvalWorkflowService->configuration($key),
            ]]
        );
        $users = User::query()->orderBy('name')->get(['id', 'name', 'username']);
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return view('pages.setting.approval.index', compact('configurations', 'users', 'roles'));
    }

    public function update(
        Request $request,
        string $workflowKey,
        ApprovalWorkflowService $approvalWorkflowService,
        TenantFeatureService $tenantFeatureService
    ) {
        abort_unless(auth()->user()->can('setting/approval/list'), 403);
        abort_unless($tenantFeatureService->isEnabled('configurable_approval'), 404);
        abort_unless(array_key_exists($workflowKey, self::WORKFLOWS), 404);

        $validated = $request->validate([
            'approver_type' => ['required', Rule::in(['user', 'role'])],
            'users' => ['nullable', 'array', 'required_if:approver_type,user', 'min:1'],
            'users.*' => ['integer', 'distinct', 'exists:users,id'],
            'roles' => ['nullable', 'array', 'required_if:approver_type,role', 'min:1'],
            'roles.*' => ['integer', 'distinct', 'exists:roles,id'],
        ]);
        $ids = $validated['approver_type'] === 'user'
            ? ($validated['users'] ?? [])
            : ($validated['roles'] ?? []);

        $approvalWorkflowService->saveConfiguration(
            $workflowKey,
            $validated['approver_type'],
            $ids
        );

        return back()->with('success', 'Konfigurasi approval berhasil disimpan.');
    }
}
