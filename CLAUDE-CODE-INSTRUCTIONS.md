# Laravel AI Auto-Translator - Complete Missing Features

## 📋 CONTEXT

I'm building a Laravel package for AI-powered translation. The package is at:
- **Source:** `C:\laragon\www\laravel-ai-translator`
- **Test app:** `D:\My-Projects\Company\laravel-translator-test`
- **Namespace:** `YoussefMekkkawy\LaravelAiTranslator`
- **Current status:** Phase 5 (95% complete)
- **Tests passing:** 110 unit tests

---

## ✅ WHAT EXISTS (Already Built & Working)

### Core Services (in `src/Services/`)
- ✅ **ViewScanner** - Scans Blade files for translation keys
- ✅ **KeyExtractor** - Processes and organizes keys
- ✅ **DeepLTranslator** - Translates using DeepL API
- ✅ **ChangeTracker** - Hash-based change detection
- ✅ **LockManager** - Protects manual edits from overwrites
- ✅ **BackupService** - Automatic backups before translation
- ✅ **LanguageFileWriter** - Writes PHP array files
- ✅ **TranslationService** - Orchestrates entire translation workflow
- ✅ **TranslatorManager** - Manages multiple AI providers

### Commands (in `src/Console/Commands/`)
- ✅ **ScanTranslationsCommand** (`lang:scan`) - Scans views for keys
- ✅ **LockTranslationCommand** (`lang:lock`) - Lock a translation
- ✅ **UnlockTranslationCommand** (`lang:unlock`) - Unlock a translation
- ✅ **ListLockedCommand** (`lang:locked`) - List locked translations
- 🐛 **TranslateCommand** (`lang:translate`) - Main command (has fixes ready)

### Tests (in `tests/`)
- ✅ 110 unit tests passing
- ✅ ViewScanner tests (9 tests)
- ✅ KeyExtractor tests (8 tests)
- ✅ DeepL translator tests (15 tests)
- ✅ Change tracking tests (12 tests)
- ✅ Lock system tests (18 tests)
- ✅ Config tests (4 tests)

---

## ❌ WHAT'S MISSING (Your Tasks)

### 1️⃣ ValidateTranslationsCommand (CRITICAL - P0)

**Create:** `src/Console/Commands/ValidateTranslationsCommand.php`

**Purpose:** Validate translation quality and detect issues

**Requirements:**

1. **Check for missing translations**
   - Key exists in source language (en) but not in target language (ar, fr, es)
   - Report which keys are missing per language

2. **Validate placeholder preservation**
   - Check that Laravel placeholders are preserved: `:name`, `:count`, `{0}`, `{1}`, etc.
   - Use regex: `/:[a-z_]+/i` for `:name` style
   - Use regex: `/\{[0-9]+\}/` for `{0}` style
   - Report mismatches (placeholder in source but not in translation)

3. **Validate HTML tag preservation**
   - Check that HTML tags are preserved: `<strong>`, `<a>`, `<em>`, etc.
   - Use regex: `/<[^>]+>/` to find tags
   - Report mismatches (tags in source but not in translation)

4. **Character count warnings**
   - Warn if translation is >3x longer than source
   - Some languages naturally longer (Arabic, German) - this is just a warning

5. **Detect duplicate keys**
   - Check if same key defined multiple times in different files
   - Report duplicates

6. **Display beautiful output**
   - Use Symfony Table component
   - Use colors (green ✓, red ✗, yellow ⚠)
   - Summary statistics at the end

**Command Signature:**
```bash
php artisan lang:validate
php artisan lang:validate --lang=ar
php artisan lang:validate --strict  # Exit with error code if warnings found
```

**Expected Output:**
```
🔍 Validating translations...

Checking ar (Arabic):
✓ All 50 keys present
⚠ 2 placeholder mismatches found:
  - auth.password_confirm: Missing :name placeholder
  - user.greeting: Missing :count placeholder
✓ All HTML tags preserved
⚠ 1 length warning:
  - welcome.long_description: 3.5x longer than source (acceptable)

Checking fr (French):
✗ 5 keys missing:
  - auth.new_key
  - auth.another_key
✓ All placeholders preserved
✓ All HTML tags preserved

📊 Summary:
  Total keys checked: 50
  Languages: 2
  Missing translations: 5
  Placeholder issues: 2
  HTML tag issues: 0
  Length warnings: 1
```

**Technical Notes:**
- Extend `Illuminate\Console\Command`
- Use `config('laravel-ai-translator')` to get config
- Load translations using Laravel's `trans()` or file reading
- Use `$this->table()` for beautiful tables
- Use `$this->info()`, `$this->error()`, `$this->warn()` for colored output
- Return exit code 0 on success, 1 on failure (when --strict)

---

### 2️⃣ RestoreCommand (CRITICAL - P0)

**Create:** `src/Console/Commands/RestoreCommand.php`

**Purpose:** Restore translations from backup

**Requirements:**

1. **List available backups**
   - Use `BackupService` to get backups from `lang/.backup/`
   - Show timestamp, file count, total size
   - Format timestamps nicely (2026-04-16 14:30:00)

2. **Interactive selection**
   - If no timestamp argument provided, show numbered list
   - Prompt user: "Which backup to restore? [1]:"
   - Validate selection

3. **Show backup details**
   - Files included in backup
   - Total size
   - Age (e.g., "5 minutes ago", "2 hours ago")

4. **Confirmation before restore**
   - Warn: "This will overwrite current translations"
   - Prompt: "Continue? (yes/no):"
   - Only proceed if user types "yes"

5. **Create safety backup**
   - Before restoring, create a new backup of current state
   - Name it: `pre-restore-{timestamp}`

6. **Restore files**
   - Copy files from backup directory to lang/
   - Preserve directory structure
   - Report progress

7. **Support flags**
   - `--latest`: Restore most recent backup without prompting
   - `--list`: Just show available backups, don't restore

**Command Signature:**
```bash
php artisan lang:restore
php artisan lang:restore 2026-04-16_14-30-00
php artisan lang:restore --latest
php artisan lang:restore --list
```

**Expected Output:**
```
📦 Available backups:

┌────┬───────────────────┬───────┬─────────┬──────────┐
│ #  │ Timestamp         │ Files │ Size    │ Age      │
├────┼───────────────────┼───────┼─────────┼──────────┤
│ 1  │ 2026-04-16 14:30  │ 15    │ 45 KB   │ 5 min    │
│ 2  │ 2026-04-16 14:25  │ 15    │ 44 KB   │ 10 min   │
│ 3  │ 2026-04-16 14:20  │ 12    │ 38 KB   │ 15 min   │
└────┴───────────────────┴───────┴─────────┴──────────┘

Which backup to restore? [1]: 1

📋 Backup details:
   Timestamp: 2026-04-16 14:30:00
   Files: 15
   Languages: ar, fr, es
   Size: 45 KB

⚠️  This will overwrite current translations
Continue? (yes/no): yes

💾 Creating safety backup first...
✓ Safety backup created: pre-restore-2026-04-16_14-35-00

🔄 Restoring files...
✓ Restored 15 files
✓ Restore complete!
```

**Technical Notes:**
- Use `BackupService->listBackups()` to get available backups
- Use `BackupService->createBackup()` for safety backup
- Use PHP's `copy()` or Laravel's `File::copy()` for restoration
- Use `$this->choice()` for interactive selection
- Use `$this->confirm()` for yes/no confirmation

---

### 3️⃣ ListBackupsCommand (CRITICAL - P0)

**Create:** `src/Console/Commands/ListBackupsCommand.php`

**Purpose:** List all available backups

**Requirements:**

1. **Read backups from BackupService**
   - Call `BackupService->listBackups()`
   - Sort by timestamp (newest first)

2. **Calculate file sizes**
   - For each backup, calculate total size of all files
   - Format nicely: KB, MB, GB

3. **Calculate age**
   - Compare backup timestamp to current time
   - Format: "5 minutes ago", "2 hours ago", "3 days ago"

4. **Display in table**
   - Use Symfony Table component
   - Columns: Timestamp, Files, Size, Age
   - Beautiful formatting

5. **Support --details flag**
   - Show additional info: languages included, file list

**Command Signature:**
```bash
php artisan lang:backup:list
php artisan lang:backup:list --details
```

**Expected Output:**
```
📦 Available backups (5 total):

┌───────────────────┬───────┬─────────┬──────────┐
│ Timestamp         │ Files │ Size    │ Age      │
├───────────────────┼───────┼─────────┼──────────┤
│ 2026-04-16 14:30  │ 15    │ 45 KB   │ 5 min    │
│ 2026-04-16 14:25  │ 15    │ 44 KB   │ 10 min   │
│ 2026-04-16 14:20  │ 12    │ 38 KB   │ 15 min   │
│ 2026-04-16 14:15  │ 12    │ 38 KB   │ 20 min   │
│ 2026-04-16 14:10  │ 10    │ 32 KB   │ 25 min   │
└───────────────────┴───────┴─────────┴──────────┘

💡 Use 'php artisan lang:restore <timestamp>' to restore a backup
```

**With --details flag:**
```
📦 Backup: 2026-04-16 14:30:00

Languages: ar, fr, es
Files: 15
Size: 45 KB
Created: 5 minutes ago

Files included:
  - lang/ar/auth.php (5 keys)
  - lang/ar/pagination.php (3 keys)
  - lang/ar/passwords.php (4 keys)
  - lang/ar/validation.php (10 keys)
  ...
```

**Technical Notes:**
- Use `Carbon` for date formatting and age calculation
- Use `File::size()` to get file sizes
- Format bytes using helper function (1024 bytes = 1 KB)

---

### 4️⃣ Feature Tests (CRITICAL - P0)

**Create:** `tests/Feature/TranslateCommandTest.php`

**Test Scenarios:**

```php
<?php

use YoussefMekkkawy\LaravelAiTranslator\Tests\TestCase;

test('translate command scans views and translates keys', function () {
    // Create test view files with translation keys
    // Run: php artisan lang:translate --force
    // Assert: Translation files created
    // Assert: Keys translated correctly
});

test('translate command respects locked translations', function () {
    // Lock a translation
    // Run translate command
    // Assert: Locked translation not changed
    // Assert: Other translations updated
});

test('translate command creates backup before translating', function () {
    // Run translate command
    // Assert: Backup directory created
    // Assert: Backup contains previous translations
});

test('translate command shows cost estimate', function () {
    // Run translate command
    // Assert: Cost estimate displayed
    // Assert: Estimate shows characters, cost, provider
});

test('translate command handles --force flag', function () {
    // Translate once
    // Change nothing
    // Run with --force
    // Assert: Re-translates everything
});

test('translate command handles --dry-run flag', function () {
    // Run with --dry-run
    // Assert: No files written
    // Assert: Shows what would be done
});

test('translate command handles --lang flag', function () {
    // Run with --lang=ar
    // Assert: Only Arabic translated
    // Assert: Other languages untouched
});

test('translate command skips unchanged keys', function () {
    // Translate once
    // Change nothing
    // Run again without --force
    // Assert: No API calls made (check mock)
    // Assert: "No changes detected" message
});

test('translate command reports accurate statistics', function () {
    // Run translate command
    // Assert: Reports correct number of keys
    // Assert: Reports correct number of languages
    // Assert: Reports correct number of files
});
```

**Create:** `tests/Feature/LockCommandsTest.php`

**Test Scenarios:**

```php
<?php

test('lock command locks a translation', function () {
    // Create translation file
    // Run: php artisan lang:lock ar auth.login
    // Assert: Lock file created
    // Assert: Translation marked as locked
});

test('unlock command unlocks a translation', function () {
    // Lock a translation first
    // Run: php artisan lang:unlock ar auth.login
    // Assert: Lock removed from file
});

test('locked command lists all locked translations', function () {
    // Lock several translations
    // Run: php artisan lang:locked
    // Assert: Shows all locked translations
    // Assert: Shows language and key
});

test('lock command validates language exists', function () {
    // Run: php artisan lang:lock xx auth.login (invalid language)
    // Assert: Error message shown
    // Assert: Command fails
});

test('lock command validates key exists', function () {
    // Run: php artisan lang:lock ar invalid.key
    // Assert: Warning shown
    // Assert: Still locks (for future keys)
});
```

**Create:** `tests/Feature/ValidateCommandTest.php`

**Test Scenarios:**

```php
<?php

test('validate command detects missing translations', function () {
    // Create source translations
    // Create incomplete target translations
    // Run: php artisan lang:validate
    // Assert: Reports missing keys
});

test('validate command detects placeholder mismatches', function () {
    // Create translation with :name placeholder
    // Create target without placeholder
    // Run validate
    // Assert: Reports placeholder issue
});

test('validate command detects HTML tag mismatches', function () {
    // Create source with <strong>text</strong>
    // Create target without tags
    // Run validate
    // Assert: Reports HTML tag issue
});
```

---

### 5️⃣ Update Documentation (IMPORTANT - P1)

**Update:** `README.md`

**Add these sections:**

1. **Complete Command Reference**
```markdown
## Commands

| Command | Description | Example |
|---------|-------------|---------|
| `lang:scan` | Scan views for translation keys | `php artisan lang:scan` |
| `lang:translate` | Translate to all languages | `php artisan lang:translate` |
| `lang:lock` | Lock a translation | `php artisan lang:lock ar auth.login` |
| `lang:unlock` | Unlock a translation | `php artisan lang:unlock ar auth.login` |
| `lang:locked` | List locked translations | `php artisan lang:locked` |
| `lang:validate` | Validate translation quality | `php artisan lang:validate` |
| `lang:restore` | Restore from backup | `php artisan lang:restore` |
| `lang:backup:list` | List available backups | `php artisan lang:backup:list` |
```

2. **Real-World Examples**
```markdown
## Examples

### First-Time Setup
\`\`\`bash
# Install package
composer require youssef-mekkkawy/laravel-ai-translator

# Configure .env
DEEPL_API_KEY=your-key-here
SUPPORTED_LANGUAGES=en,ar,fr,es

# Run translation
php artisan lang:translate
\`\`\`

### Daily Workflow
\`\`\`bash
# 1. Add new translation keys to views
{{ __('dashboard.new_feature') }}

# 2. Run translation
php artisan lang:translate

# 3. Review translations (optional)
php artisan lang:validate

# 4. Lock any manual improvements
php artisan lang:lock ar dashboard.new_feature
\`\`\`
```

3. **Configuration Guide**
```markdown
## Configuration

All configuration is in `config/laravel-ai-translator.php`:

\`\`\`php
return [
    // AI Provider
    'driver' => 'deepl',
    
    // Languages
    'languages' => ['en', 'ar', 'fr', 'es'],
    'default_language' => 'en',
    
    // Scanning
    'scan_paths' => [resource_path('views')],
    'exclude_files' => ['vendor/**', 'node_modules/**'],
    
    // Backup
    'backup' => [
        'enabled' => true,
        'keep' => 5,  // Keep last 5 backups
    ],
];
\`\`\`
```

4. **Troubleshooting Section**
```markdown
## Troubleshooting

### "DeepL API key not configured"
**Solution:** Add `DEEPL_API_KEY=xxx` to your `.env` file

### "No translation keys found"
**Solution:** Make sure you're using `{{ __('key') }}` syntax in your Blade views

### "Translation files not created"
**Solution:** Check that `lang/` directory exists and is writable

### "Locked translation was overwritten"
**Solution:** Lock wasn't applied. Run `php artisan lang:lock <lang> <key>`
```

**Create:** `docs/USAGE.md`

**Include:**

1. **Step-by-Step Tutorial** - First translation from scratch
2. **Lock Management Guide** - When and how to lock translations
3. **Backup & Restore Guide** - Safety best practices
4. **Cost Optimization Tips** - How to minimize API costs
5. **Multi-Developer Workflow** - Working in teams

---

## 🔧 TECHNICAL REQUIREMENTS

### Code Style
- ✅ Follow Laravel conventions
- ✅ Use type hints on all methods: `public function methodName(string $param): array`
- ✅ PHPDoc blocks on all classes and methods
- ✅ Follow existing code patterns in the package
- ✅ Use Symfony Console components for tables/progress bars

### Testing
- ✅ Use Pest framework (not PHPUnit directly)
- ✅ Follow existing test patterns in `tests/Unit/`
- ✅ Extend `TestCase` from `tests/TestCase.php`
- ✅ Mock external dependencies (DeepL API, file system)
- ✅ Target: 90%+ code coverage

### Error Handling
- ✅ Validate all inputs
- ✅ Show helpful error messages
- ✅ Use Laravel's Validator where appropriate
- ✅ Never crash - always graceful failures
- ✅ Return proper exit codes (0 = success, 1 = failure)

### Output Style
- ✅ Use `$this->info()`, `$this->error()`, `$this->warn()`
- ✅ Beautiful tables using Symfony Table component
- ✅ Progress bars for long operations
- ✅ Emojis for visual clarity: ✓ ✗ ⚠ 📊 🚀 💰 🔍

---

## 📚 EXISTING SERVICES YOU CAN USE

### Core Services (src/Services/)

**BackupService** - Located at `src/Services/BackupService.php`
```php
// Methods available:
public function createBackup(string $suffix = ''): string  // Returns backup path
public function listBackups(): array  // Returns array of backup info
public function restore(string $timestamp): bool
public function cleanup(): void  // Remove old backups
```

**LockManager** - Located at `src/Services/Lock/LockManager.php`
```php
// Methods available:
public function isLocked(string $language, string $key): bool
public function lock(string $language, string $key, ?string $reason = null): void
public function unlock(string $language, string $key): void
public function listLocked(?string $language = null): array
```

**TranslationService** - Located at `src/Services/TranslationService.php`
```php
// Methods available:
public function translateAll(array $targetLanguages, bool $force = false): array
public function estimateCost(array $targetLanguages, bool $force = false): array
```

**ViewScanner** - Located at `src/Services/Scanner/ViewScanner.php`
```php
// Methods available:
public function scanAll(): array  // Returns flat array of unique keys
public function scanForBladeFiles(): array  // Returns array of file paths
public function getStatistics(): array
```

---

## 🗂️ CONFIG STRUCTURE

Available via `config('laravel-ai-translator')`:

```php
[
    'driver' => 'deepl',
    'languages' => ['en', 'ar', 'fr', 'es'],
    'default_language' => 'en',
    'scan_paths' => [resource_path('views')],
    'exclude_files' => ['vendor/**', 'node_modules/**', 'tests/**'],
    
    'backup' => [
        'enabled' => true,
        'path' => base_path('lang/.backup'),
        'keep' => 5,
        'cleanup' => true,
    ],
    
    'providers' => [
        'deepl' => [
            'api_key' => env('DEEPL_API_KEY'),
            'plan' => env('DEEPL_PLAN', 'free'),
        ],
    ],
    
    'storage' => [
        'metadata_file' => base_path('lang/.translations-meta.json'),
        'lock_file' => base_path('lang/.locked-translations.json'),
    ],
]
```

---

## 📁 FILE STRUCTURE

### Where to Create Files

**Commands:**
```
src/Console/Commands/
├── ValidateTranslationsCommand.php  ← CREATE THIS
├── RestoreCommand.php                ← CREATE THIS
└── ListBackupsCommand.php            ← CREATE THIS
```

**Tests:**
```
tests/Feature/
├── TranslateCommandTest.php          ← CREATE THIS
├── LockCommandsTest.php              ← CREATE THIS
└── ValidateCommandTest.php           ← CREATE THIS
```

**Documentation:**
```
docs/
└── USAGE.md                          ← CREATE THIS

README.md                              ← UPDATE THIS
```

### Register Commands

Add new commands in `src/LaravelAiTranslatorServiceProvider.php`:

```php
public function boot(): void
{
    // ... existing code ...
    
    if ($this->app->runningInConsole()) {
        $this->commands([
            \YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ScanTranslationsCommand::class,
            \YoussefMekkkawy\LaravelAiTranslator\Console\Commands\TranslateCommand::class,
            \YoussefMekkkawy\LaravelAiTranslator\Console\Commands\LockTranslationCommand::class,
            \YoussefMekkkawy\LaravelAiTranslator\Console\Commands\UnlockTranslationCommand::class,
            \YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ListLockedCommand::class,
            
            // ADD THESE:
            \YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ValidateTranslationsCommand::class,
            \YoussefMekkkawy\LaravelAiTranslator\Console\Commands\RestoreCommand::class,
            \YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ListBackupsCommand::class,
        ]);
    }
}
```

---

## 🎯 PRIORITY ORDER

Build in this order:

1. **ValidateTranslationsCommand** (Most critical - quality assurance)
2. **RestoreCommand** (Safety feature - important for users)
3. **ListBackupsCommand** (Utility - nice to have)
4. **Feature Tests** (Quality assurance)
5. **Documentation** (User-facing)

---

## 📝 OUTPUT REQUIREMENTS

For each file you create:

1. ✅ Show the complete file content
2. ✅ Explain what it does
3. ✅ Show where to save it (full path)
4. ✅ Show how to test it (command + expected output)
5. ✅ Note any dependencies or prerequisites

---

## 🧪 TESTING INSTRUCTIONS

After creating each command:

```bash
# Navigate to package
cd C:\laragon\www\laravel-ai-translator

# Rebuild autoload
composer dump-autoload

# Run unit tests
composer test

# Test in Laravel app
cd D:\My-Projects\Company\laravel-translator-test

# Clear cache
php artisan cache:clear
php artisan config:clear

# Reinstall package
Remove-Item -Recurse -Force vendor\youssef-mekkkawy\laravel-ai-translator
composer dump-autoload

# Test the command
php artisan lang:validate
php artisan lang:restore --list
php artisan lang:backup:list
```

---

## ✅ COMPLETION CHECKLIST

When finished, verify:

- [ ] ValidateTranslationsCommand created and working
- [ ] RestoreCommand created and working
- [ ] ListBackupsCommand created and working
- [ ] All commands registered in ServiceProvider
- [ ] Feature tests created and passing
- [ ] Documentation updated (README.md)
- [ ] USAGE.md created
- [ ] All tests passing (run `composer test`)
- [ ] No errors when running commands
- [ ] Beautiful output with colors and emojis

---

## ❓ QUESTIONS?

If you need clarification on:
- How a specific service works
- What format data is in
- How existing code patterns work
- Anything else

**Ask me!** I can provide examples from existing code or explain any pattern.

---

## 🚀 READY TO BUILD!

Start with **ValidateTranslationsCommand** - it's the most important for users to verify translation quality.

Good luck! 🎉
