<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantMenuEnabled
{
    public function __construct(private TenantFeatureService $tenantFeatures) {}

    public function handle(Request $request, Closure $next): Response
    {
        $path = trim($request->path(), '/');
        if ($path === 'setting/features' || str_starts_with($path, 'setting/features/')) {
            return $next($request);
        }

        if ($path === 'inputNomorRekanan' || $path === 'laporan/nomor-notaris/update') {
            $featureKey = $this->numberReportFeatureKey((string) $request->input('kategori', ''));
            abort_unless($featureKey && $this->tenantFeatures->isEnabled($featureKey), 404);

            return $next($request);
        }
        if (preg_match('#^laporan/nomor-notaris/export/([^/]+)$#', $path, $matches)) {
            $featureKey = $this->numberReportFeatureKey($matches[1]);
            abort_unless($featureKey && $this->tenantFeatures->isEnabled($featureKey), 404);

            return $next($request);
        }
        if (($path === 'arsip/bundle' || str_starts_with($path, 'arsip/bundle/'))
            && ! $request->isMethod('GET')
            && $request->input('kategori')) {
            $featureKey = $request->input('kategori') === 'ppat'
                ? 'menu_arsip_ppat'
                : 'menu_arsip_akta';
            abort_unless($this->tenantFeatures->isEnabled($featureKey), 404);

            return $next($request);
        }

        $matches = [];
        foreach (config('tenant_features', []) as $featureKey => $feature) {
            if (! ($feature['menu'] ?? false)) {
                continue;
            }
            foreach ($feature['paths'] ?? [] as $pathRule) {
                if (is_string($pathRule)) {
                    [$prefix, $queryString] = array_pad(explode('?', $pathRule, 2), 2, null);
                    $queryRules = $queryString === null ? [] : (array) [];
                    if ($queryString !== null) {
                        parse_str($queryString, $queryRules);
                    }
                    $exceptQueryRules = [];
                } else {
                    $prefix = $pathRule['path'];
                    $queryRules = $pathRule['query'] ?? [];
                    $exceptQueryRules = $pathRule['except_query'] ?? [];
                }

                if (($path === $prefix || str_starts_with($path, $prefix.'/'))
                    && collect($queryRules)->every(fn ($value, $key): bool => (string) $request->query($key) === (string) $value)
                    && collect($exceptQueryRules)->every(fn ($value, $key): bool => (string) $request->query($key) !== (string) $value)) {
                    $matches[] = ['length' => strlen($prefix), 'feature' => $featureKey];
                }
            }
        }
        usort($matches, static fn (array $left, array $right): int => $right['length'] <=> $left['length']);
        if ($matches !== []) {
            abort_unless($this->tenantFeatures->isEnabled($matches[0]['feature']), 404);
        }

        return $next($request);
    }

    private function numberReportFeatureKey(string $category): ?string
    {
        return [
            'notaris' => 'menu_laporan_nomor_notaris',
            'ppat' => 'menu_laporan_nomor_ppat',
            'waarmerking' => 'menu_laporan_nomor_waarmerking',
            'covernot' => 'menu_laporan_nomor_covernot',
            'surat-keluar' => 'menu_laporan_nomor_surat_keluar',
            'legalisasi' => 'menu_laporan_nomor_legalisasi',
            'wasiat' => 'menu_laporan_nomor_wasiat',
        ][$category] ?? null;
    }
}
