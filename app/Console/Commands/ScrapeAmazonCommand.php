<?php

namespace App\Console\Commands;

use App\Actions\ScrapeProductsAction;
use Illuminate\Console\Command;

class ScrapeAmazonCommand extends Command
{
  protected $signature = 'scrape:products {url} {--limit=10 : Maximum number of products to scrape}';
  protected $description = 'Scrape products from eCommerce sites (Amazon, Jumia, etc.)';

  private ScrapeProductsAction $scrapeAction;

  public function __construct(ScrapeProductsAction $scrapeAction)
  {
    parent::__construct();
    $this->scrapeAction = $scrapeAction;
  }

  public function handle()
  {
    $url = $this->argument('url');
    $limit = (int) $this->option('limit');

    $site = str_contains($url, 'amazon') ? 'Amazon' : 'Jumia';
    $this->info("Starting to scrape {$site} products from {$url}");

    try {
      $products = $this->scrapeAction->execute($url, $limit);

      if (empty($products)) {
        $this->warn('No products found');
        return 0;
      }

      $this->info("Successfully scraped " . count($products) . " products:");

      foreach ($products as $product) {
        $this->line("- {$product->title} | EGP {$product->price}");
      }

      $this->newLine();
      $this->info('Successfully scraped products');
    } catch (\Exception $e) {
      if (str_contains($e->getMessage(), 'bot activity')) {
        $this->error("Amazon blocked the request. Try using:");
        $this->line("1. Real proxy servers");
        $this->line("2. Different user agents");
        $this->line("3. Lower request frequency");
        $this->line("4. VPN or different IP address");
      } else {
        $this->error("Error scraping {$site}: " . $e->getMessage());
      }
      return 1;
    }

    return 0;
  }
}
