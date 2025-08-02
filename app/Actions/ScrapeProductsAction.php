<?php

namespace App\Actions;

use App\Services\ProxyService;
use App\Services\AmazonScraperService;
use App\Services\JumiaScraperService;
use Illuminate\Support\Facades\Http;

class ScrapeProductsAction
{
    private array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:89.0) Gecko/20100101 Firefox/89.0',
    ];

    public function __construct(
        private ProxyService $proxyService,
        private AmazonScraperService $amazonScraper,
        private JumiaScraperService $jumiaScraper
    ) {}

    public function execute(string $url, int $limit = 10): array
    {
        try {
            $proxy = $this->proxyService->get();
            $httpClient = Http::withHeaders([
                'User-Agent' => $this->userAgents[array_rand($this->userAgents)],
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
            ])->timeout(30);

            if ($proxy && $proxy['url'] !== 'direct') {
                $httpClient = $httpClient->withOptions(['proxy' => $proxy['url']]);
            }

            
            $response = $httpClient->get($url);
            
            
            if (!$response->successful()) {
                return [];
            }

            $html = $response->body();
            
            if (str_contains($url, 'amazon')) {
                return $this->amazonScraper->extract($html, $limit);
            } elseif (str_contains($url, 'jumia')) {
                return $this->jumiaScraper->extract($html, $limit);
            }
            
            return [];

        } catch (\Exception $e) {
            if (isset($proxy) && $proxy) {
                $this->proxyService->markFailed($proxy['url']);
            }
            return [];
        }
    }
}