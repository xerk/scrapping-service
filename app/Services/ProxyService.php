<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ProxyService
{
    public function get(): ?array
    {
        try {
            $response = Http::timeout(5)->get('http://localhost:8080/proxy');
            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function markFailed(string $proxyUrl): void
    {
        try {
            Http::timeout(5)->post('http://localhost:8080/proxy/failed', ['proxy_url' => $proxyUrl]);
        } catch (\Exception $e) {}
    }
}