<?php

namespace App\Services;

use App\Models\Product;
use DOMDocument;
use DOMXPath;

class JumiaScraperService
{
    public function extract(string $html, int $limit): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $products = [];
        $count = 0;

        $productSelectors = [
            '//article[contains(@class, "prd") and contains(@class, "c-prd")]',
            '//article[contains(@class, "prd")]'
        ];

        foreach ($productSelectors as $selector) {
            $productNodes = $xpath->query($selector);
            
            foreach ($productNodes as $node) {
                if ($count >= $limit) break 2;

                $productData = $this->extractProduct($xpath, $node);
                
                if ($productData && $productData['title'] && $productData['price']) {
                    try {
                        $product = Product::create($productData);
                        $products[] = $product;
                        $count++;
                        usleep(500000);
                    } catch (\Exception $e) {}
                }
            }
        }

        return $products;
    }

    private function extractProduct(DOMXPath $xpath, \DOMNode $productNode): ?array
    {
        $title = $this->extractTitle($xpath, $productNode);
        $price = $this->extractPrice($xpath, $productNode);
        $imageUrl = $this->extractImage($xpath, $productNode);

        return ($title && $price) ? [
            'title' => $title,
            'price' => $price,
            'image_url' => $imageUrl ?? '',
        ] : null;
    }

    private function extractTitle(DOMXPath $xpath, \DOMNode $productNode): ?string
    {
        $selectors = [
            './/h3[@class="name"]',
            './/div[@class="info"]//h3[@class="name"]',
            './/a[@class="core"]//div[@class="info"]//h3[@class="name"]'
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $productNode);
            if ($nodes->length > 0) {
                $title = trim($nodes->item(0)->textContent);
                if ($title && strlen($title) > 5) {
                    return $title;
                }
            }
        }

        return null;
    }

    private function extractPrice(DOMXPath $xpath, \DOMNode $productNode): ?float
    {
        $selectors = [
            './/div[@class="prc"]',
            './/div[@class="info"]//div[@class="prc"]',
            './/a[@class="core"]//div[@class="info"]//div[@class="prc"]'
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $productNode);
            if ($nodes->length > 0) {
                $priceText = trim($nodes->item(0)->textContent);
                $price = $this->parsePrice($priceText);
                if ($price !== null && $price > 0) {
                    return $price;
                }
            }
        }

        return null;
    }

    private function extractImage(DOMXPath $xpath, \DOMNode $productNode): ?string
    {
        $selectors = [
            './/div[@class="img-c"]//img/@src',
            './/div[@class="img-c"]//img/@data-src',
            './/a[@class="core"]//div[@class="img-c"]//img/@src',
            './/img/@src'
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector, $productNode);
            if ($nodes->length > 0) {
                $imageUrl = trim($nodes->item(0)->textContent);
                if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    return $imageUrl;
                } elseif ($imageUrl && str_starts_with($imageUrl, '//')) {
                    return 'https:' . $imageUrl;
                } elseif ($imageUrl && str_starts_with($imageUrl, '/')) {
                    return 'https://www.jumia.com.eg' . $imageUrl;
                }
            }
        }

        return null;
    }

    private function parsePrice(string $priceText): ?float
    {
        $priceText = preg_replace('/[^\d.,]/', '', $priceText);
        
        if (strpos($priceText, ',') !== false && strpos($priceText, '.') !== false) {
            $priceText = str_replace(',', '', $priceText);
        } elseif (strpos($priceText, ',') !== false) {
            $parts = explode(',', $priceText);
            if (count($parts) == 2 && strlen(end($parts)) <= 2) {
                $priceText = str_replace(',', '.', $priceText);
            } else {
                $priceText = str_replace(',', '', $priceText);
            }
        }
        
        if (preg_match('/(\d+(?:\.\d+)?)/', $priceText, $matches)) {
            return floatval($matches[1]);
        }

        return null;
    }
}