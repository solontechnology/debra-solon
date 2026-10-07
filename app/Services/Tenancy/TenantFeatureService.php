<?php

namespace App\Services\Tenancy;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TenantFeatureService
{
    private const REQUEST_CACHE_KEY = 'tenant.features.enabled';

    public function all(): array
    {
        $features = config('tenant_features', []);
        $enabledFeatures = DB::table('tenant_features')
            ->whereIn('feature_key', array_keys($features))
            ->pluck('enabled', 'feature_key');

        foreach ($features as $key => $feature) {
            $features[$key]['enabled'] = array_key_exists($key, $enabledFeatures->all())
                ? (bool) $enabledFeatures[$key]
                : (bool) $feature['default'];
        }
        request()->attributes->set(
            self::REQUEST_CACHE_KEY,
            collect($features)->mapWithKeys(fn (array $feature, string $key): array => [$key => $feature['enabled']])->all()
        );

        return $features;
    }

    public function isEnabled(string $featureKey): bool
    {
        $feature = config("tenant_features.{$featureKey}");
        if (! is_array($feature)) {
            throw new InvalidArgumentException("Unknown tenant feature [{$featureKey}].");
        }
        if (($feature['menu'] ?? false) && auth()->user()?->hasRole('super admin')) {
            return true;
        }

        $requestCache = request()->attributes->get(self::REQUEST_CACHE_KEY, []);
        if (array_key_exists($featureKey, $requestCache)) {
            return $requestCache[$featureKey];
        }

        $enabled = DB::table('tenant_features')
            ->where('feature_key', $featureKey)
            ->value('enabled');

        $enabled = $enabled === null
            ? (bool) $feature['default']
            : (bool) $enabled;
        $requestCache[$featureKey] = $enabled;
        request()->attributes->set(self::REQUEST_CACHE_KEY, $requestCache);

        return $enabled;
    }

    public function anyEnabled(array $featureKeys): bool
    {
        foreach ($featureKeys as $featureKey) {
            if ($this->isEnabled($featureKey)) {
                return true;
            }
        }

        return false;
    }

    public function setEnabled(string $featureKey, bool $enabled): void
    {
        if (! is_array(config("tenant_features.{$featureKey}"))) {
            throw new InvalidArgumentException("Unknown tenant feature [{$featureKey}].");
        }

        $now = now();
        DB::table('tenant_features')->upsert(
            [[
                'feature_key' => $featureKey,
                'enabled' => $enabled,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['feature_key'],
            ['enabled', 'updated_at']
        );
        $requestCache = request()->attributes->get(self::REQUEST_CACHE_KEY, []);
        $requestCache[$featureKey] = $enabled;
        request()->attributes->set(self::REQUEST_CACHE_KEY, $requestCache);
    }
}
