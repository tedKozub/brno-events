# studyBrno / Brno Events

Open-source aggregator, search engine, and interactive directory of university and student events, venues, and study spaces in Brno, Czech Republic.

---

## Technical Overview

The application is built as a high-performance, single-instance platform optimized for low latency, zero external API costs, and strict data normalization:

- **Backend:** Laravel 12 on PHP 8.4 (PHP-CLI / PHP-FPM, SQLite with WAL mode).
- **Frontend:** Livewire 3, Alpine.js, Tailwind CSS, DaisyUI.
- **Search Engine:** Hybrid full-text and semantic vector search using 384-dimensional embeddings (`BAAI/bge-small-en-v1.5` / fastembed ONNX) with vector cosine similarity computed directly in PHP.
- **Venue & Geodata Engine:** Fully normalized 3NF venue schema (`venues`, `venue_aliases`, `geocoding_cache`). Physical entities store coordinates; events link to venues while preserving room/hall details (`room_detail`). Includes an in-memory alias dictionary and an OpenStreetMap (Nominatim) rate-limited geocoding pipeline.
- **Event Harvester:** Multi-source pipeline extracting event data from university portals (MUNI, VUT, MENDELU, VETUNI, UNOB), iCal feeds, RSS, and social media. Features raw daily snapshot archiving, SHA-256 card slicing, and local LLM extraction.
- **Analytics:** Lightweight, cookieless, GDPR-compliant analytics tracking visits and outbound clicks without personal data collection.
- **Administration:** Filament 3 admin panel for event review, venue management, and system monitoring.

---

## System Requirements

- **PHP:** 8.2 or higher with `pdo_sqlite`, `mbstring`, `curl`, `dom`, `fileinfo` extensions
- **Composer:** 2.x
- **Node.js:** 20.x or higher with npm
- **Python:** 3.11 or higher (for scraping and raw harvesting)
- **SQLite:** 3.35 or higher

---

## Installation & Setup

### 1. Clone Repository & Install Dependencies

```bash
git clone https://github.com/tedKozub/brno-events.git
cd brno-events

composer install
npm install
npm run build
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Configure `APP_URL`, `APP_TIMEZONE` (`Europe/Prague`), and database settings in `.env`.

### 3. Database Initialization

```bash
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force
php artisan venues:seed-aliases --resolve-events
```

### 4. Scraper Virtual Environment

```bash
cd scrapers
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
playwright install chromium
cd ..
```

### 5. Running Development Server

```bash
# Start backend server
php artisan serve --port=8085

# Start frontend asset compiler
npm run dev
```

---

## Testing & Verification

Automated test suites cover backend business logic, scraper parsers, and browser-based end-to-end interactions.

```bash
# Backend unit and feature tests (PHPUnit)
./vendor/bin/phpunit

# Scraper parser tests (Python unittest)
scrapers/.venv/bin/python3 -m unittest discover -s scrapers/tests/

# End-to-end browser tests (Playwright)
npm run test:e2e:desktop
```

---

## Command Reference

### Scraping & Data Pipeline

```bash
# Scrape all active sources defined in config/scrape_sources.yaml
php artisan events:scrape

# Scrape a specific source with a limit
php artisan events:scrape 1 --limit=10

# Archive raw HTML/JSON snapshots without parsing
php artisan events:harvest-raw

# Seed venue aliases and re-link event locations
php artisan venues:seed-aliases --resolve-events

# Enrich missing event posters and banners
php artisan events:enrich-banners
```

### Maintenance

```bash
# Clear application configuration cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Legal Notice & Licensing

- Content aggregation operates within the reporting fair-use framework under Section 34(1)(c) of the Czech Copyright Act (Act No. 121/2000 Coll.) to inform students about public educational, cultural, and academic events.
- All scraped event records provide attribution and a direct link to the organizer's original publication URL.
- This project is released under the [MIT License](LICENSE).
