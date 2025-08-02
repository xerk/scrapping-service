# eCommerce Product Scraper

A web scraping service for extracting product data from eCommerce websites using Laravel backend, Next.js frontend, and Go proxy management.

## Features

- Multi-site scraping (Amazon, Jumia)
- Laravel REST API for product management
- Next.js frontend with auto-refresh every 30 seconds
- Go proxy rotation service with health monitoring
- User-agent rotation to avoid detection
- MySQL database storage

## Quick Start

### 1. Backend Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```
Backend runs on: `http://localhost:8000`

### 2. Frontend Setup
```bash
cd frontend
pnpm install
pnpm dev
```
Frontend runs on: `http://localhost:3000`

### 3. Proxy Service (Optional)
```bash
cd proxy-service
go run main.go
```
Proxy service runs on: `http://localhost:8080`

## Usage Examples

### Scrape Products from Different Sites

#### Amazon Egypt Electronics
```bash
php artisan scrape:products "https://www.amazon.com/s?k=laptop" --limit=10
```

#### Jumia Egypt Phones & Tablets
```bash
php artisan scrape:products "https://www.jumia.com.eg/ar/phones-tablets/#catalog-listing" --limit=10
```

### View Results

#### Web Interface
- **Products Page**: http://localhost:3000/products (auto-refreshes every 30s)


#### API Endpoints
- **Get Products**: `GET http://localhost:8000/api/products`

### API Usage Examples

#### Get All Products
```bash
curl http://localhost:8000/api/products
```


### Database Configuration
Default: SQLite (no setup required)
For MySQL: Update `.env` file
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scrapper_db
DB_USERNAME=root
DB_PASSWORD=
```
