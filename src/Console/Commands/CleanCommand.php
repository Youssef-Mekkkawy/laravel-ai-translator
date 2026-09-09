<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;

class CleanCommand extends Command
{
    protected $signature   = 'lang:clean
                                {--dry-run : Show unused keys without deleting}
                                {--force   : Delete without confirmation}';

    protected $description = 'Find and remove unused translation keys';

    public function handle(): int
    {
        $this->newLine();
        $this->line('  🧹 <fg=bright-green>Laravel AI Translator — Clean Unused Keys</>');
        $this->newLine();

        $config     = config('ai-translator', []);
        $sourceLang = $config['default_language'] ?? 'en';
        $languages  = $config['languages'] ?? ['en'];
        $scanPaths  = array_filter(
            $config['scan_paths'] ?? [resource_path('views')],
            fn ($p) => is_dir($p)
        );

        // Step 1 — Scan views for used keys
        $this->line('  <fg=gray>Scanning codebase for translation keys in use...</>');
        $scanner  = new ViewScanner(array_values($scanPaths));
        $usedKeys = array_flip($scanner->scanAll()); // flip for O(1) lookup

        // Also include JSON keys from source file
        $jsonPath = lang_path($sourceLang . '.json');
        $jsonUsedKeys = [];
        if (File::exists($jsonPath)) {
            $json = @json_decode(File::get($jsonPath), true);
            if (is_array($json)) {
                foreach (array_keys($json) as $key) {
                    $usedKeys[$key] = true;
                }
                $jsonUsedKeys = array_keys($json);
            }
        }

        $this->line("  <fg=gray>Found " . count($usedKeys) . " keys in use.</>");
        $this->newLine();

        // Step 2 — Load keys from SOURCE language files (primary reference)
        $sourceKeys = [];

        // Try PHP source files first (lang/en/)
        $sourceLangPath = lang_path($sourceLang);
        $phpSourceKeys  = $this->loadAllKeys($sourceLangPath);
        foreach ($phpSourceKeys as $fullKey => $meta) {
            $sourceKeys[$fullKey] = $meta;
        }

        // Try JSON source file (lang/en.json)
        $jsonSourcePath = lang_path($sourceLang . '.json');
        if (File::exists($jsonSourcePath)) {
            $json = @json_decode(File::get($jsonSourcePath), true);
            if (is_array($json)) {
                foreach (array_keys($json) as $key) {
                    $sourceKeys[$key] = ['file' => '__json__', 'key' => $key];
                }
            }
        }

        // Fallback — if no source files, use ALL translated files
        if (empty($sourceKeys)) {
            $this->line("  <fg=yellow>⚠️  No source files found in lang/{$sourceLang}/ — falling back to all translation files.</>");
            foreach ($languages as $lang) {
                $langPath = lang_path($lang);
                foreach ($this->loadAllKeys($langPath) as $fullKey => $meta) {
                    if (!isset($sourceKeys[$fullKey])) {
                        $sourceKeys[$fullKey] = $meta;
                    }
                }
                $jsonPath = lang_path($lang . '.json');
                if (File::exists($jsonPath)) {
                    $json = @json_decode(File::get($jsonPath), true);
                    if (is_array($json)) {
                        foreach (array_keys($json) as $key) {
                            if (!isset($sourceKeys[$key])) {
                                $sourceKeys[$key] = ['file' => '__json__', 'key' => $key];
                            }
                        }
                    }
                }
            }
        }

        if (empty($sourceKeys)) {
            $this->line("  <fg=yellow>⚠️  No translation files found. Run lang:translate first.</>");
            $this->newLine();
            return self::SUCCESS;
        }

        $this->line("  <fg=gray>Found " . count($sourceKeys) . " keys in source files.</>");
        $this->newLine();

        // Step 3 — Find unused keys (in source files but NOT used in views)
        $unusedKeys = [];
        foreach ($sourceKeys as $fullKey => $meta) {
            if (!isset($usedKeys[$fullKey])) {
                $unusedKeys[$fullKey] = $meta;
            }
        }

        if (empty($unusedKeys)) {
            $this->line('  <fg=green>✅ All translation keys are in use. Nothing to clean.</>');
            $this->newLine();
            return self::SUCCESS;
        }

        // Step 4 — Display unused keys
        $this->line("  <fg=yellow>Found " . count($unusedKeys) . " unused key(s):</>");
        $this->newLine();

        $byFile = [];
        foreach ($unusedKeys as $fullKey => $meta) {
            $byFile[$meta['file']][] = $fullKey;
        }

        foreach ($byFile as $file => $keys) {
            $label = $file === '__json__' ? "lang/{$sourceLang}.json" : "{$file}.php";
            $this->line("  <fg=cyan>  {$label}</>");
            foreach ($keys as $key) {
                $this->line("    <fg=red>✗</> {$key}");
            }
            $this->newLine();
        }

        // Step 5 — Dry run stops here
        if ($this->option('dry-run')) {
            $this->line('  <fg=gray>Dry run — no changes made.</>');
            $this->newLine();
            return self::SUCCESS;
        }

        // Step 6 — Confirm deletion
        if (!$this->option('force')) {
            if (!$this->confirm("  Delete these " . count($unusedKeys) . " unused key(s) from all language files?", false)) {
                $this->line('  <fg=gray>Cancelled.</> No changes made.');
                $this->newLine();
                return self::SUCCESS;
            }
        }

        // Step 7 — Delete from all language files
        $deleted  = 0;
        $allLangs = $languages; // Delete from ALL language files

        foreach ($unusedKeys as $fullKey => $meta) {
            foreach ($allLangs as $lang) {
                if ($meta['file'] === '__json__') {
                    // Remove from JSON file
                    $jsonFile = lang_path("{$lang}.json");
                    if (!File::exists($jsonFile)) continue;
                    $json = @json_decode(File::get($jsonFile), true);
                    if (!is_array($json)) continue;
                    unset($json[$fullKey]);
                    ksort($json);
                    File::put($jsonFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
                } else {
                    // Remove from PHP file
                    $filePath = lang_path("{$lang}/{$meta['file']}.php");
                    if (!File::exists($filePath)) continue;
                    $translations = @include $filePath;
                    if (!is_array($translations)) continue;
                    $keyParts     = explode('.', $meta['key']);
                    $translations = $this->removeNestedKey($translations, $keyParts);
                    File::put($filePath, $this->generatePhpFile($translations));
                }
                $deleted++;
            }
        }

        $this->newLine();
        $this->line("  <fg=green>✅ Deleted " . count($unusedKeys) . " unused key(s) from " . count($allLangs) . " language file(s).</>");
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function loadAllKeys(string $langPath): array
    {
        $keys  = [];

        if (!File::exists($langPath)) {
            return $keys;
        }

        foreach (File::files($langPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group        = $file->getBasename('.php');
            $translations = @include $file->getPathname();

            if (!is_array($translations)) {
                continue;
            }

            foreach ($this->flattenKeys($translations, $group) as $fullKey => $nestedKey) {
                $keys[$fullKey] = [
                    'file' => $group,
                    'key'  => $nestedKey,
                ];
            }
        }

        return $keys;
    }

    private function flattenKeys(array $array, string $prefix = '', string $keyPrefix = ''): array
    {
        $result = [];

        foreach ($array as $k => $v) {
            $fullKey   = $prefix ? "{$prefix}.{$k}" : $k;
            $nestedKey = $keyPrefix ? "{$keyPrefix}.{$k}" : $k;

            if (is_array($v)) {
                $result = array_merge($result, $this->flattenKeys($v, $fullKey, $nestedKey));
            } else {
                $result[$fullKey] = $nestedKey;
            }
        }

        return $result;
    }

    private function removeNestedKey(array $array, array $keys): array
    {
        $key = array_shift($keys);

        if (empty($keys)) {
            unset($array[$key]);
        } elseif (isset($array[$key]) && is_array($array[$key])) {
            $array[$key] = $this->removeNestedKey($array[$key], $keys);
            if (empty($array[$key])) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    private function generatePhpFile(array $translations): string
    {
        return "<?php\n\nreturn " . $this->arrayToPhp($translations) . ";\n";
    }

    private function arrayToPhp(array $array, int $depth = 0): string
    {
        if (empty($array)) {
            return '[]';
        }

        $indent     = str_repeat('    ', $depth);
        $nextIndent = str_repeat('    ', $depth + 1);
        $lines      = ['['];

        foreach ($array as $key => $value) {
            $formattedKey = "'" . addslashes((string) $key) . "'";

            if (is_array($value)) {
                $lines[] = "{$nextIndent}{$formattedKey} => " . $this->arrayToPhp($value, $depth + 1) . ',';
            } else {
                $lines[] = "{$nextIndent}{$formattedKey} => '" . addslashes((string) $value) . "',";
            }
        }

        $lines[] = "{$indent}]";

        return implode("\n", $lines);
    }
}
