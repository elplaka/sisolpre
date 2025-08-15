<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    public static function get(string $key, $default = null): ?string
    {
        return Cache::rememberForever("settings.{$key}", function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value): void
    {
        Cache::forget("settings.{$key}");

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}