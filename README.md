<p align="center">
  <h1 align="center">🎓 BrnoAkce – Studentské akce Brno</h1>
  <p align="center">
    <strong>Moderní, rychlá a otevřená platforma sdružující všechny studentské akce, festivaly, workshopy a komunitní život v Brně na jednom místě.</strong>
  </p>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12" />
  <img src="https://img.shields.io/badge/Livewire-3.x-FB70A9?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire 3" />
  <img src="https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS" />
  <img src="https://img.shields.io/badge/Python_Scraper-3.11-3776AB?style=for-the-badge&logo=python&logoColor=white" alt="Python 3.11" />
  <img src="https://img.shields.io/badge/Playwright-E2E-2EAD33?style=for-the-badge&logo=playwright&logoColor=white" alt="Playwright" />
</p>

---

## 🌟 Hlavní funkce platformy

* **⚡ Dynamický Feed akcí:** Okamžité filtrování podle univerzit (**MUNI**, **VUT**, **MENDELU**, **UNOB**, **VETUNI**), kategorií a vstupného s plynulým načítáním dalších dávek a tlačítkem *Scroll to Top*.
* **🧠 Sémantické vektorové vyhledávání:** Vyhledávání založené na 384-dimenzionálních embeddingových vektorech s bleskovou kosinovou podobností přímo v PHP. Najde *„pivo“*, i když je v popisu jen *„hospodský kvíz“*.
* **🗺️ Interaktivní Mapa Brna:** Zobrazení akcí i studentských míst (`/mista`) na mapovém podkladu OpenStreetMap přes Leaflet.js s podporou dotykových gest a zoomování.
* **🖼️ Full-screen Media Lightbox:** Zobrazení originálních plakátů ve vysokém rozlišení. Zavírá se intuitivně kliknutím kamkoliv mimo plakát nebo klávesou `Escape`, bez rušivých prvků.
* **🤖 Multimodální AI Scraping:** Automatické vytěžování akcí z univerzitních webů, iCal kalendářů, RSS kanálů i sociálních sítí (Instagram & Facebook) s využitím Playwright a OpenAI / Gemini Vision pro čtení grafických plakátů.
* **📊 Cookieless Anonymní Analytika:** 100% v souladu s GDPR, žádné ukládání IP adres, automatická detekce státu a možnost vyloučení interního testovacího provozu na 1 kliknutí.
* **📱 Progresivní Webová Aplikace (PWA):** Responzivní mobilní zobrazení, instalace na plochu telefonu, podpora tmavého i světlého režimu.
* **🛡️ Administrace & Moderace:** Správa akcí, zdrojů, schvalovací fronta a přehledné statistiky ve Filament 3 (`/admin`).

---

## 🏗️ Architektura a Pravidla

Kompletní technická specifikace, datové toky, právní mantinely (§ 34 autorského zákona) a pravidla pro vývojáře a budoucí AI agenty naleznete v samostatném dokumentu:

👉 **[Přečíst ARCHITECTURE.md](ARCHITECTURE.md)**

---

## 🚀 Rychlý start (Vývojové prostředí)

### Požadavky
* **PHP:** 8.4+ s rozšířeními `pdo_sqlite`, `mbstring`, `curl`, `dom`, `fileinfo`
* **Node.js:** 20+ a npm
* **Python:** 3.11+ (pro scrapery)
* **Composer**

### Instalace krok za krokem

```bash
# 1. Klonování repozitáře
git clone https://github.com/tedKozub/brno-events.git
cd brno-events

# 2. Instalace PHP závislostí
composer install

# 3. Instalace JS závislostí a sestavení stylů
npm install
npm run build

# 4. Příprava konfiguračního souboru
cp .env.example .env
php artisan key:generate

# 5. Inicializace SQLite databáze a migrace
touch database/database.sqlite
php artisan migrate --seed

# 6. Příprava Python virtuálního prostředí pro scrapery
cd scrapers
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
playwright install chromium
cd ..
```

### Spuštění vývojového serveru

```bash
# Spuštění aplikace
php artisan serve --port=8085

# Sledování změn frontendu (Vite)
npm run dev
```

---

## 🧪 Spouštění testů (Quality Assurance)

Každá úprava v projektu podléhá přísnému testování. Před odesláním do větve `master` musí projít všechny testy:

```bash
# Backend unit a feature testy (PHPUnit)
php artisan test

# E2E testy v prohlížeči (Playwright Desktop Chrome)
npm run test:e2e:desktop

# E2E testy v mobilním viewportu (iPhone 14)
npx playwright test --project="Mobile iPhone 14"
```

---

## 📜 Správa Scraperů

Zdroje pro scraping jsou deklarativně definovány v [`config/scrape_sources.yaml`](config/scrape_sources.yaml).

```bash
# Synchronizace YAML definic do databáze
php artisan scrape-sources:sync

# Spuštění scrapingu všech aktivních zdrojů
php artisan events:scrape

# Spuštění konkrétního zdroje podle ID
php artisan events:scrape 8

# Dotipování plakátů a bannerů k již existujícím akcím
php artisan events:enrich-banners
```

---

## ⚖️ Právní rámec & Licence

* Platforma operuje v režimu **zpravodajské licence podle § 34 odst. 1 písm. c) autorského zákona (zákon č. 121/2000 Sb.)** pro informování studentů o veřejných univerzitních událostech.
* Všechny akce a plakáty uvádějí odkaz na původní zdroj pořadatele. Pořadatelé mohou kdykoliv požádat o stažení či úpravu přes integrovaný formulář nahlášení nebo na e-mailu správce.
* Tento projekt je licencován pod licencí [MIT](LICENSE).
