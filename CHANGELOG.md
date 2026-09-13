# Changelog

All notable changes to `laravel-ai-translator` are documented here.

Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).  
Versioning follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.12] — 2026-09-13

### Added
- **Background translation queue** — translation runs in a separate process via `lang:work-queue`, web server never blocks during translation. Works on all platforms (Windows, Linux, macOS, Docker, Valet, Herd, shared hosting) using `PHP_BINARY` — zero extra dependencies
- **`lang:work-queue`** — background worker command spawned automatically, never needs to be run manually
- **`GET /api/translate/status`** — polling endpoint for real-time translation progress
- **`POST /api/translate/reset`** — emergency queue reset endpoint
- **`lang:clean`** — find and remove unused translation keys from language files
- **`ai-translator:install`** wizard — interactive setup with stack detection, provider selection, language configuration, `.env` update, and view scanning in one command
- **Auto stack detection** — detects Blade, Blade+Livewire, Inertia+Vue, Inertia+React, and Breeze automatically via `StackDetector`
- **JSON file support** — reads and writes `lang/en.json` alongside PHP files for Breeze/Vue/React apps
- **Auto source file creation** — when views reference keys missing from source files, `lang/en.json` and `lang/en/*.php` are created automatically before translation
- **Full ISO 639-1 language list** — 180+ languages available in the "Add language" modal
- **Auto-translate on language add** — adding a language via the dashboard immediately queues its translation in the background
- **RuntimeConfig** (`lang/.ai-translator-runtime.json`) — dashboard settings (provider, model, languages) stored in a JSON file, no `.env` writes, no server restarts
- **Dashboard language filtering** — "Add language" modal reads RuntimeConfig so already-configured languages are correctly hidden
- **Stats fix** — `missingCount` and `translatedCount` exclude brand-new untranslated languages from the aggregate, preventing inflated numbers
- **`OverviewController`** — server-rendered stats now read from RuntimeConfig and include JSON source keys, eliminating the flash of wrong numbers on page load
- **`TranslateCommand::getTargetLanguages()`** — reads RuntimeConfig first so dashboard-added languages are always included in CLI translate runs
- **`InstallCommand`** — sanitizes language input (trims spaces, removes duplicates) and writes selected languages to RuntimeConfig immediately
- **`ViewScanner::scanForBladeFiles()`** — public method for scanning files
- **`ViewScanner::scanFile(string $path)`** — public method for per-file key extraction
- **`ViewScanner::getStatistics()`** — auto-triggers scan if not yet run
- **Directory component exclude matching** — exclude patterns like `vendor` now correctly exclude files inside `vendor/` subdirectories
- **`DashboardController`** — passes `cfgLangs` from RuntimeConfig to all views via `sharedData()`
- **`SettingsController`** — reads driver, model, chunk size, and context from RuntimeConfig (reflects dashboard changes after save)
- **`OllamaStartController`** — removed `pullModelBackground()` from `status()` polling endpoint; only `start()` triggers a pull
- **`BackupsController`** — backup settings (keepLast, autoBackup) now saveable via the dashboard
- Support for PHP 8.2+, Laravel 10.x, 11.x, 12.x, 13.x
- 120+ tests

### Fixed
- Translate button was blocking the web server — entire site froze during translation
- Duplicate `translateAll()` call in `TranslateCommand` was zeroing out results on second call
- `TranslationService` was creating the correct scanner from RuntimeConfig then discarding it, falling back to the defaults-only injected scanner
- Languages page showed wrong coverage percentages — `loadLangKeys()` was reading PHP files only, ignoring `lang/{locale}.json`
- Overview stats showed wrong numbers on page load — server-rendered values used static config, JS refresh corrected them after load
- New languages added via dashboard were not being translated — `TranslateCommand` read `config('ai-translator.languages')` instead of RuntimeConfig
- Settings page showed stale provider/model after saving — was reading static config instead of RuntimeConfig
- Sidebar "Active provider" chip never updated after settings change
- History page always showed "Ollama $0.00" — provider and model were never recorded in run history
- `OllamaStartController::status()` triggered `pullModelBackground()` on every poll (every 2 seconds), spawning multiple background pulls
- Backup settings (keepLast, autoBackup) had no save handler — inputs existed but values were never persisted
- `ViewScanner` extension check matched `not-blade.php` as a blade file — fixed by requiring the dot prefix
- `filteredAddOptions` in dashboard used static config for configured languages — dashboard-added languages still appeared as available to add again
- `ai-translator:install` wrote languages to `.env` but not RuntimeConfig — `lang:translate` ignored the install selection

---

## [1.0.0] — TBD

First stable release.
