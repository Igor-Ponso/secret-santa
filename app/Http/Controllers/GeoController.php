<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoController extends Controller
{
    /**
     * Lightweight IP geolocation proxy (server-side) to avoid exposing third-party API key to the browser.
     * Uses ipapi.co (no key for basic info) and caches results for 24h.
     */
    public function ip(Request $request)
    {
        $ip = $request->query('ip');
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return response()->json(['error' => 'invalid_ip'], 422);
        }
        $cacheKey = 'geo_ip_' . $ip;
        $data = Cache::remember($cacheKey, now()->addDay(), function () use ($ip) {
            try {
                $resp = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");
                if (!$resp->ok()) {
                    return null;
                }
                $json = $resp->json();
                return [
                    'ip' => $ip,
                    'country' => $json['country_name'] ?? null,
                    'region' => $json['region'] ?? null,
                    'city' => $json['city'] ?? null,
                    'latitude' => isset($json['latitude']) ? (float) $json['latitude'] : null,
                    'longitude' => isset($json['longitude']) ? (float) $json['longitude'] : null,
                ];
            } catch (\Throwable $e) {
                return null; // silent failure -> client can fallback
            }
        });
        return response()->json(['data' => $data]);
    }
}
